<?php

namespace App\Filament\Company\Resources\InvoiceResource\Pages;

use App\Models\Account;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\StoreTransaction;
use App\Models\FinancialDocument;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingService;
use App\Models\StoreTransactionItem;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Company\Resources\InvoiceResource;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * قبل از ذخیره، داده‌های فرم را اعتبارسنجی و تغییر می‌دهد.
     *
     * @param array $data
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Log::info('Form data before save:', $data);

        if (empty($this->record->items())) {
            throw new \InvalidArgumentException('فاکتور باید حداقل یک آیتم داشته باشد.');
        }

        foreach ($this->record->items() as $item) {
            if ($item['quantity'] <= 0) {
                throw new \InvalidArgumentException("مقدار آیتم باید مثبت باشد.");
            }
        }

        return $data;
    }

    /**
     * پس از ذخیره فاکتور، اسناد مالی، تراکنش‌های انبار و موجودی را به‌روزرسانی می‌کند.
     *
     * @throws \InvalidArgumentException
     */
    protected function afterSave(): void
    {
        try {
            DB::transaction(function () {
                $invoice = $this->record;
                $supplier = $invoice->person;
                $inventoryAccount = Account::where('code', $supplier->accounting_code)->first();

                // اعتبارسنجی
                if (!$supplier) {
                    throw new \InvalidArgumentException("تأمین‌کننده برای فاکتور {$invoice->id} یافت نشد.");
                }
                if (!$supplier->account) {
                    throw new \InvalidArgumentException("حساب تأمین‌کننده {$supplier->id} یافت نشد.");
                }
                if (!$inventoryAccount) {
                    throw new \InvalidArgumentException("حساب انبار با کد {$supplier->accounting_code} یافت نشد.");
                }

                // ذخیره مقادیر قدیمی آیتم‌ها برای محاسبه مابه‌التفاوت
                $oldItems = StoreTransactionItem::whereHas('storeTransaction', function ($query) use ($invoice) {
                    $query->where('reference', 'INV-' . $invoice->number);
                })->get()->keyBy('product_id');

                // حذف داده‌های قبلی بدون اجرای ایونت‌ها
                StoreTransaction::withoutEvents(function () use ($invoice) {
                    $oldStoreTransaction = StoreTransaction::where('reference', 'INV-' . $invoice->number)->first();
                    if ($oldStoreTransaction) {
                        StoreTransactionItem::withoutEvents(function () use ($oldStoreTransaction) {
                            StoreTransactionItem::where('store_transaction_id', $oldStoreTransaction->id)->delete();
                        });
                        $oldStoreTransaction->delete();
                    }
                });

                // حذف اسناد مالی قبلی
                $oldDocument = FinancialDocument::where('invoice_id', $invoice->id)->first();
                if ($oldDocument) {
                    Transaction::where('financial_document_id', $oldDocument->id)->delete();
                    $oldDocument->delete();
                }

                // بازسازی اسناد مالی
                AccountingService::createFinancialDocument($invoice, $inventoryAccount, $supplier->account);

                // بازسازی تراکنش انبار بدون اجرای ایونت‌ها
                StoreTransaction::withoutEvents(function () use ($invoice, $oldItems) {
                    $store_transaction = StoreTransaction::create([
                        'store_id' => $invoice->store_id,
                        'type' => 'entry',
                        'date' => $invoice->date,
                        'reference' => 'INV-' . $invoice->number,
                    ]);

                    // به‌روزرسانی آیتم‌ها و موجودی با مابه‌التفاوت
                    foreach ($invoice->items as $item) {
                        if ($item->quantity <= 0) {
                            throw new \InvalidArgumentException("مقدار آیتم {$item->id} باید مثبت باشد.");
                        }

                        // محاسبه مابه‌التفاوت
                        $oldQuantity = $oldItems->has($item->product_id) ? $oldItems[$item->product_id]->quantity : 0;
                        $quantityDiff = $item->quantity - $oldQuantity;

                        // ایجاد آیتم تراکنش انبار بدون اجرای ایونت‌ها
                        StoreTransactionItem::withoutEvents(function () use ($store_transaction, $item) {
                            StoreTransactionItem::create([
                                'store_transaction_id' => $store_transaction->id,
                                'product_id' => $item->product_id,
                                'quantity' => $item->quantity,
                            ]);
                        });

                        // به‌روزرسانی موجودی با مابه‌التفاوت
                        if ($quantityDiff != 0) {
                            $update_type = ($quantityDiff > 0) ? 'entry' : 'exit';
                            $product = Product::findOrFail($item->product_id);
                            InventoryService::updateInventory($product, $invoice->store, abs($quantityDiff), $update_type);
                        }

                        // به‌روزرسانی قیمت خرید
                        Product::withoutEvents(function () use ($item) {
                            Product::where('id', $item->product_id)->update([
                                'purchase_price' => $item->unit_price,
                            ]);
                        });
                    }
                });
            });
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطا در ویرایش فاکتور')
                ->body($e->getMessage())
                ->danger()
                ->send();
            throw $e;
        }
    }
}