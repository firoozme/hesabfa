<?php

namespace App\Filament\Company\Resources\InvoiceResource\Pages;

use Filament\Actions;
use App\Models\Person;
use App\Models\Account;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\StoreTransaction;
use App\Models\FinancialDocument;
use App\Models\AccountingDocument;
use App\Models\StoreTransactionItem;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Company\Resources\InvoiceResource;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['company_id'] = auth()->user('company')->id;
        return $data;
    }

    protected function afterSave(): void
    {
        $invoice = $this->record;

        foreach ($invoice->items as $item) {
            Product::where('id',$item->product_id)->update([
                'purchase_price' => $item->unit_price
            ]);
        }
    //     $invoice = $this->record;
    //     $formData = $this->form->getState();

    //     // لاگ‌گذاری برای دیباگ
    //     \Illuminate\Support\Facades\Log::info('Form items: ' . json_encode($formData['items']));

    //     // حذف تراکنش قدیمی
    //     $oldTransaction = StoreTransaction::where('reference', 'INV-' . $invoice->number)->first();
    //     if ($oldTransaction) {
    //         foreach ($oldTransaction->items as $item) {
    //             $product = Product::find($item->product_id);
    //             if ($product) {
    //                 $product->inventory -= $item->quantity;
    //                 $product->save();
    //             }
    //         }
    //         $oldTransaction->items()->delete();
    //         $oldTransaction->delete();
    //     }

    //     // ثبت تراکنش جدید
    //     $transaction = StoreTransaction::create([
    //         'store_id' => $invoice->store_id,
    //         'type' => 'entry',
    //         'date' => $invoice->date,
    //         'reference' => 'INV-' . $invoice->number,
    //     ]);

    //     // ثبت آیتم‌های جدید
    //     foreach ($formData['items'] as $item) {
    //         if (isset($item['product_id']) && !empty($item['product_id']) && isset($item['quantity'])) {
    //             StoreTransactionItem::create([
    //                 'store_transaction_id' => $transaction->id,
    //                 'product_id' => $item['product_id'],
    //                 'quantity' => $item['quantity'],
    //             ]);

    //             $product = Product::find($item['product_id']);
    //             if ($product) {
    //                 $product->inventory += $item['quantity'];
    //                 $product->save();
    //             }
    //         } else {
    //             \Illuminate\Support\Facades\Log::error('Invalid item data: ' . json_encode($item));
    //         }
    //     }

    //     // آپدیت اسناد حسابداری
    //     $accountingDocument = AccountingDocument::where('reference', 'DOC-' . $invoice->number)->first();
    //     if ($accountingDocument) {
    //         $accountingDocument->update([
    //             'date' => $invoice->date,
    //             'description' => 'فاکتور خرید ' . $invoice->number,
    //         ]);
    //     }

    //     // آپدیت سند مالی
    //     $financialDocument = FinancialDocument::where('document_number', 'DOC-' . $invoice->number)->first();
    //     if ($financialDocument) {
    //         $financialDocument->transactions()->delete();
    //         $financialDocument->update([
    //             'date' => $invoice->date,
    //             'description' => 'فاکتور خرید ' . $invoice->number,
    //         ]);

    //         $supplier = $invoice->person;
    //         $inventoryAccount = Account::where('code', $supplier->accounting_code)->first();

    //         if (!$supplier || !$supplier->account || !$inventoryAccount) {
    //             throw new \Exception('حساب انبار یا تأمین‌کننده پیدا نشد.');
    //         }

    //         Transaction::create([
    //             'financial_document_id' => $financialDocument->id,
    //             'account_id' => $inventoryAccount->id,
    //             'account_type' => Account::class,
    //             'debit' => $invoice->total_amount,
    //             'credit' => 0,
    //             'description' => 'افزایش موجودی انبار',
    //         ]);

    //         Transaction::create([
    //             'financial_document_id' => $financialDocument->id,
    //             'account_id' => $supplier->account->id,
    //             'account_type' => Account::class,
    //             'debit' => 0,
    //             'credit' => $invoice->total_amount,
    //             'description' => 'بدهی به تأمین‌کننده ' . $supplier->fullname,
    //         ]);
    //     }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}