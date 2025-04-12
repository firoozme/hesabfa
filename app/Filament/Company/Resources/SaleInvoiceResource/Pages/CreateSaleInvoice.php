<?php

namespace App\Filament\Company\Resources\SaleInvoiceResource\Pages;

use Filament\Actions;
use App\Models\Person;
use App\Models\Account;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\StoreTransaction;
use App\Models\FinancialDocument;
use App\Models\AccountingDocument;
use Illuminate\Support\Facades\DB;
use App\Models\StoreTransactionItem;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Company\Resources\SaleInvoiceResource;

class CreateSaleInvoice extends CreateRecord
{
    protected static string $resource = SaleInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user('company')->id;
        $data['type'] = 'sale';
        return $data;
    }

    protected function afterCreate(): void
    {
        DB::transaction(function () {
            $invoice = $this->record;

            if ($invoice->items->isEmpty()) {
                throw new \Exception('فاکتور باید حداقل یک آیتم داشته باشد.');
            }

            $transaction = StoreTransaction::create([
                'store_id' => $invoice->store_id,
                'type' => 'exit',
                'date' => $invoice->date,
                'reference' => 'SALE-' . $invoice->number,
            ]);

            foreach ($invoice->items as $item) {
                StoreTransactionItem::create([
                    'store_transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ]);
            }

            $document = FinancialDocument::create([
                'document_number' => 'DOC-SALE-' . $invoice->number,
                'date' => $invoice->date,
                'description' => 'فاکتور فروش ' . $invoice->number,
                'company_id' => $invoice->company_id,
            ]);

            $customer = $invoice->person;
            $inventoryAccount = Account::where('code', $customer->accounting_code)->first(); // حساب انبار جداگانه

            if (!$customer || !$customer->account || !$inventoryAccount) {
                throw new \Exception('حساب انبار یا مشتری پیدا نشد.');
            }

            Transaction::create([
                'financial_document_id' => $document->id,
                'account_id' => $inventoryAccount->id,
                'account_type' => Account::class,
                'debit' => 0,
                'credit' => $invoice->total_amount,
                'description' => 'کاهش موجودی انبار برای فروش',
            ]);

            Transaction::create([
                'financial_document_id' => $document->id,
                'account_id' => $customer->account->id,
                'account_type' => Account::class,
                'debit' => $invoice->total_amount,
                'credit' => 0,
                'description' => 'دریافتنی از مشتری ' . $customer->fullname,
            ]);
        });

        Notification::make()
            ->title('فاکتور فروش با موفقیت ثبت شد')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
   
}