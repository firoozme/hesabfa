<?php
namespace App\Filament\Company\Resources\InvoiceResource\Pages;

use App\Models\Person;
use App\Models\Account;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Actions\Action;
use App\Models\StoreTransaction;
use App\Models\FinancialDocument;
use Illuminate\Support\Facades\DB;
use App\Models\StoreTransactionItem;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Company\Resources\InvoiceResource;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth('company')->user()->id;

        return $data;
    }
    // protected function getFormActions(): array
    // {
    //     return [
          
               
    //             Action::make('create_and_pay')
    //                 ->label('ایجاد و چاپ')
    //                 ->color('success')
    //                 ->action(function ($livewire, $data) {
    //                     // ایجاد فاکتور
    //                     $formData = $this->form->getState();
                        
    //                     // الحاق company_id به داده‌های فرم
    //                     $formData['company_id'] = auth('company')->user()->id;

    //                     $record = $this->handleRecordCreation($formData);
                                      
    //                     return redirect()->route('invoice.pdf', ['id' => $record->id]);
    //                 }),
    //                 ...parent::getFormActions(),
    //     ];
    // }
    protected function afterCreate(): void
    {
       
        // dd($this->invoice->items);
        DB::transaction(function () {
            $invoice = $this->record;

            // اطمینان از اینکه فاکتور حداقل یک آیتم دارد
            if ($invoice->items->isEmpty()) {
                throw new \Exception('فاکتور باید حداقل یک آیتم داشته باشد.');
            }

            // ثبت تراکنش ورود
            $transaction = StoreTransaction::create([
                'store_id'  => $invoice->store_id,
                'type'      => 'entry',
                'date'      => $invoice->date,
                'reference' => 'INV-' . $invoice->number,
            ]);

            foreach ($invoice->items as $item) {
                StoreTransactionItem::create([
                    'store_transaction_id' => $transaction->id,
                    'product_id'           => $item->product_id,
                    'quantity'             => $item->quantity,
                ]);

                Product::where('id',$item->product_id)->update([
                    'purchase_price' => $item->unit_price
                ]);
            }

            // ثبت اسناد مالی و حسابداری
            $document = FinancialDocument::create([
                'document_number' => 'DOC-' . $invoice->number,
                'date'            => $invoice->date,
                'description'     => 'فاکتور خرید ' . $invoice->number,
                'company_id'      => $invoice->company_id,
                'invoice_id'      =>  $invoice->id
            ]);

            $supplier         = $invoice->person;
            $inventoryAccount = Account::where('code', $supplier->accounting_code)->first();
            if (! $supplier || ! $supplier->account || ! $inventoryAccount) {
                throw new \Exception('حساب انبار یا تأمین‌کننده پیدا نشد.');
            }

            Transaction::create([
                'financial_document_id' => $document->id,
                'account_id'            => $inventoryAccount->id,
                'account_type'          => Account::class,
                'debit'                 => $invoice->total_amount,
                'credit'                => 0,
                'description'           => 'افزایش موجودی انبار',
            ]);

            Transaction::create([
                'financial_document_id' => $document->id,
                'account_id'            => $supplier->account->id,
                'account_type'          => Account::class,
                'debit'                 => 0,
                'credit'                => $invoice->total_amount,
                'description'           => 'بدهی به تأمین‌کننده ' . $supplier->fullname,
            ]);
        });

        // نمایش اعلان موفقیت
        // Notification::make()
        //     ->title('فاکتور با موفقیت ثبت شد')
        //     ->success()
        //     ->send();
    }

    protected function afterDelete(): void
    {
        DB::transaction(function () {
            $invoice = $this->record;

            // پیدا کردن و حذف تراکنش انبار مرتبط
            $storeTransaction = StoreTransaction::where('reference', 'INV-' . $invoice->number)->first();
            if ($storeTransaction) {
                // حذف آیتم‌های تراکنش انبار
                StoreTransactionItem::where('store_transaction_id', $storeTransaction->id)->delete();
                // حذف خود تراکنش انبار
                $storeTransaction->delete();
            }

            // پیدا کردن و حذف سند مالی مرتبط
            $financialDocument = FinancialDocument::where('document_number', 'DOC-' . $invoice->number)->first();
            if ($financialDocument) {
                // حذف تراکنش‌های مالی مرتبط
                Transaction::where('financial_document_id', $financialDocument->id)->delete();
                // حذف خود سند مالی
                $financialDocument->delete();
            }
        });

        // نمایش اعلان موفقیت (در صورت نیاز)
        // Notification::make()
        //     ->title('فاکتور با موفقیت حذف شد')
        //     ->success()
        //     ->send();
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
