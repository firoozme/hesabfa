<?php

namespace App\Filament\Company\Resources\SaleInvoiceResource\Pages;

use Filament\Actions;
use App\Models\Account;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Actions\Action;
use App\Models\StoreTransaction;
use App\Models\FinancialDocument;
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
        $data['company_id'] = auth('company')->user()->id;
        $data['type'] = 'sale';

        return $data;
    }
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Action::make('createAndPrint')
            ->label('ایجاد و چاپ')
            ->action(function () {
                $this->create();
        
                $url = route('invoice.pdf', ['id' => $this->record->id]);
                $this->js("window.open('{$url}', '_blank')");
            })
            ->color('success'),
            Action::make('createAndPay')
            ->label('ایجاد و پرداخت')
            ->action(function () {
                $this->create();
        
                $url = route('filament.company.resources.invoices.receipts', ['record' => $this->record]);
                $this->js("window.open('{$url}', '_blank')");
            })
            ->color('warning')
        

        ];
    }

    protected function afterCreate(): void
    {
        // dd($this->record);
        // "accounting_auto" => "auto"
        // "number" => 1
        // "date" => "2025-08-17"
        // "name" => null
        // "person_id" => 1
        // "store_id" => "1"
        // "company_id" => 1
        // "updated_at" => "2025-08-17 17:37:57"
        // "created_at" => "2025-08-17 17:37:57"
        // "id" => 1

        DB::transaction(function () {

            // Variables
            $invoice          = $this->record;
            $customer         = $invoice->person;
            $inventoryAccount = Account::where('code', $customer->accounting_code)->first();

            // Ensure that the invoice has at least one item
            if ($invoice->items->isEmpty()) {
                Notification::make()
                    ->title('فاکتور باید حداقل یک آیتم داشته باشد')
                    ->danger()
                    ->send();
                return;
            }

            // Ensure that a warehouse account or customer exists
            if (! $customer || ! $customer->account || ! $inventoryAccount) {
                Notification::make()
                    ->title('حساب انبار یا تأمین‌کننده پیدا نشد')
                    ->danger()
                    ->send();
                return;
            }

            // Invoice Items
            //// Already Created in Repeator relationship()

            // Financial Documents
            $document = FinancialDocument::create([
                'document_number' => 'DOC-' . $invoice->number,
                'date'            => $invoice->date,
                'description'     => 'فاکتور فروش ' . $invoice->number,
                'company_id'      => $invoice->company_id,
                'invoice_id'      => $invoice->id,
            ]);

            // Transactions
            Transaction::create([
                'financial_document_id' => $document->id,
                'account_id'            => $inventoryAccount->id,
                'account_type'          => Account::class,
                'debit'                 => 0,
                'credit'                => $invoice->total_amount,
                'description'           => 'کاهش موجودی انبار برای فاکتور فروش',
            ]);

            Transaction::create([
                'financial_document_id' => $document->id,
                'account_id'            => $customer->account->id,
                'account_type'          => Account::class,
                'debit'                 => $invoice->total_amount,
                'credit'                => 0,
                'description'           => 'طلب از مشتری ' . $customer->fullname,
            ]);

            // store_transaction
            $store_transaction = StoreTransaction::create([
                'store_id'  => $invoice->store_id,
                'type'      => 'exit',
                'date'      => $invoice->date,
                'reference' => 'SALE-INV-' . $invoice->number,
            ]);

            // store_product
            // Automatically Created in StoreTransaction Model

            // store_transaction_items
            foreach ($invoice->items as $item) {
                StoreTransactionItem::create([
                    'store_transaction_id' => $store_transaction->id,
                    'product_id'           => $item->product_id,
                    'quantity'             => $item->quantity,
                ]);

                
                DB::table('products')
                ->where('id', $item->product_id)
                ->update(['purchase_price' => $item->unit_price]);
                }
        });

    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
