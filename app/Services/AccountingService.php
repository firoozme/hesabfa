<?php
namespace App\Services;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\FinancialDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    /**
     * ایجاد سند مالی و تراکنش‌ها برای فاکتور.
     *
     * @param Invoice $invoice
     * @param Account $inventoryAccount
     * @param Account $supplierAccount
     * @throws \Exception
     */
    
    public static function createFinancialDocument(Invoice $invoice, Account $inventoryAccount, Account $supplierAccount): FinancialDocument
    {
        return DB::transaction(function () use ($invoice, $inventoryAccount, $supplierAccount) {
            $document = FinancialDocument::create([
                'document_number' => 'DOC-' . $invoice->number,
                'date' => $invoice->date,
                'description' => 'فاکتور خرید ' . $invoice->number,
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
            ]);

            Transaction::create([
                'financial_document_id' => $document->id,
                'account_id' => $inventoryAccount->id,
                'account_type' => Account::class,
                'debit' => $invoice->total_amount,
                'credit' => 0,
                'description' => 'افزایش موجودی انبار',
            ]);

            Transaction::create([
                'financial_document_id' => $document->id,
                'account_id' => $supplierAccount->id,
                'account_type' => Account::class,
                'debit' => 0,
                'description' => 'بدهی به تأمین‌کننده ' . $supplierAccount->name ?? '',
                'description' => 'بدهی به تأمین‌کننده ' . '',
            ]);

            return $document;
        });
    }

    /**
     * ایجاد سند مالی معکوس برای فاکتور برگشت خرید.
     */
    public static function createReturnFinancialDocument(Invoice $invoice, Account $inventoryAccount, Account $supplierAccount): FinancialDocument
    {
        return DB::transaction(function () use ($invoice, $inventoryAccount, $supplierAccount) {
            $document = FinancialDocument::create([
                'document_number' => 'PR-DOC-' . $invoice->number,
                'date' => $invoice->date,
                'description' => 'فاکتور برگشت خرید ' . $invoice->number,
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
            ]);

            Transaction::create([
                'financial_document_id' => $document->id,
                'account_id' => $inventoryAccount->id,
                'account_type' => Account::class,
                'debit' => 0,
                'credit' => $invoice->total_amount,
                'description' => 'کاهش موجودی انبار (برگشت خرید)',
            ]);

            Transaction::create([
                'financial_document_id' => $document->id,
                'account_id' => $supplierAccount->id,
                'account_type' => Account::class,
                'debit' => $invoice->total_amount,
                'credit' => 0,
                'description' => 'کاهش بدهی به تأمین‌کننده ' . $supplierAccount->person?->fullname ?? '',
            ]);

            return $document;
        });
    }

     /**
     * حذف اسناد مالی و تراکنش‌های مرتبط با یک فاکتور.
     *
     * @param Invoice $invoice
     * @return void
     * @throws \Exception
     */
    public static function deleteFinancialDocument(Invoice $invoice)
    {
        try {
            DB::transaction(function () use ($invoice) {
                // یافتن اسناد مالی مرتبط با فاکتور
                $financialDocuments = FinancialDocument::where('invoice_id', $invoice->id)->get();

                if ($financialDocuments->isEmpty()) {
                    Log::info("هیچ سند مالی برای فاکتور {$invoice->id} یافت نشد.");
                    return;
                }

                foreach ($financialDocuments as $document) {
                    // حذف تراکنش‌های مرتبط در جدول transactions
                    $transactions = Transaction::where('financial_document_id', $document->id)->get();
                    foreach ($transactions as $transaction) {
                        // به‌روزرسانی تعادل حساب‌ها
                        $account = Account::find($transaction->account_id);
                        if ($account) {
                            if ($transaction->type === 'debit') {
                                $account->update([
                                    'balance' => $account->balance - $transaction->amount,
                                ]);
                            } elseif ($transaction->type === 'credit') {
                                $account->update([
                                    'balance' => $account->balance + $transaction->amount,
                                ]);
                            }
                        }

                        // حذف نرم تراکنش
                        $transaction->delete();
                    }

                    // حذف سند مالی
                    $document->delete();

                    Log::info("سند مالی {$document->id} و تراکنش‌های مرتبط با موفقیت حذف شدند.");
                }
            });
        } catch (\Exception $e) {
            Log::error("خطا در حذف اسناد مالی فاکتور {$invoice->id}: " . $e->getMessage());
            throw $e;
        }
    }
}