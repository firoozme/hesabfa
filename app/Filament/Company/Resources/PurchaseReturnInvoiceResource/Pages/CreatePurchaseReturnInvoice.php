<?php

namespace App\Filament\Company\Resources\PurchaseReturnInvoiceResource\Pages;

use Filament\Actions;
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
use Illuminate\Validation\ValidationException;
use App\Filament\Company\Resources\InvoiceResource;
use App\Filament\Company\Resources\PurchaseReturnInvoiceResource;

class CreatePurchaseReturnInvoice extends CreateRecord
{
    protected static string $resource = PurchaseReturnInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user('company')->id;
        $data['type'] = 'purchase_return';

        return $data;
    }

    // protected function afterCreate(): void
    // {
    //     $error =false;
    //     DB::transaction(function () {
    //     $return = $this->record;

    //     // چک کردن موجودی قبل از ثبت تراکنش

    //     // ثبت تراکنش خروج
    //     $transaction = StoreTransaction::create([
    //         'store_id' => $return->store_id,
    //         'type' => 'exit',
    //         'date' => $return->date,
    //         'reference' => 'RET-' . $return->number,
    //     ]);

    //     // ثبت آیتم‌ها و به‌روزرسانی موجودی
    //     foreach ($return->items as $item) {
    //         StoreTransactionItem::create([
    //             'store_transaction_id' => $transaction->id,
    //             'product_id' => $item->product_id,
    //             'quantity' => $item->quantity,
    //         ]);
    //     }

    //     // ثبت سند حسابداری
    //     $accounting_document = AccountingDocument::create([
    //         'reference' => 'RET-' . $return->number,
    //         'date' => $return->date,
    //         'description' => 'برگشت خرید ' . $return->number,
    //         'company_id' => $return->company_id,
    //     ]);

    //     $document = FinancialDocument::create([
    //         'document_number' => 'RET-' . $return->number,
    //         'date' => $return->date,
    //         'description' => 'برگشت خرید ' . $return->number,
    //         'company_id' => $return->company_id,
    //     ]);

    //     $supplier = $return->person;
    //     $inventoryAccount = Account::where('code', $supplier->accounting_code)->first();

    //     if (!$supplier || !$supplier->account || !$inventoryAccount) {
    //         throw new \Exception('حساب انبار یا تأمین‌کننده پیدا نشد.');
    //     }

    //     // معکوس کردن حسابداری فاکتور خرید
    //     Transaction::create([
    //         'financial_document_id' => $document->id,
    //         'account_id' => $inventoryAccount->id,
    //         'account_type' => Account::class,
    //         'debit' => 0,
    //         'credit' => $return->total_amount, // کاهش موجودی انبار
    //         'description' => 'کاهش موجودی انبار به دلیل برگشت',
    //     ]);

    //     Transaction::create([
    //         'financial_document_id' => $document->id,
    //         'account_id' => $supplier->account->id,
    //         'account_type' => Account::class,
    //         'debit' => $return->total_amount, // کاهش بدهی تأمین‌کننده
    //         'credit' => 0,
    //         'description' => 'کاهش بدهی تأمین‌کننده ' . $supplier->fullname,
    //     ]);
    // });
    // }

    // protected function getRedirectUrl(): string
    // {
    //     return InvoiceResource::getUrl('index');
    // }

    // // نمایش خطا به کاربر
    // protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    // {
    //     try {
    //         return parent::handleRecordCreation($data);
    //     } catch (\Exception $e) {
    //         // $this->notify('danger', $e->getMessage());
    //         throw  $e->getMessage(); // متوقف کردن فرآیند
    //     }
    // }
}
