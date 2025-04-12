<?php
namespace App\Models;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Transaction;
use App\Traits\LogsActivity;
use App\Models\AccountingDocument;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class Payment extends Model
{
    use LogsActivity;
    protected $fillable = ['invoice_id', 'paymentable_type', 'paymentable_id', 'amount'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function paymentable()
    {
        return $this->morphTo();
    }

    protected static function booted()
    {
        static::created(function ($payment) {
            $invoice = $payment->payable;
            // dd($invoice);
            $person = $invoice->person ?? null; // تأمین‌کننده از فاکتور
            // dd($person, $person->account); // برای调试

            // if (!$person) {
            //     Notification::make()
            //         ->title('خطا در ثبت پرداخت')
            //         ->body('حساب تأمین‌کننده پیدا نشد. لطفاً حساب را تعریف کنید.')
            //         ->danger()
            //         ->send();
            //     throw new \Exception('حساب تأمین‌کننده پیدا نشد.');
            // }

            $accountingdocument = AccountingDocument::create([
                'reference' => 'PAY-' . $invoice->number . '-' . now()->timestamp,
                'date' => now(),
                'description' => 'پرداخت فاکتور شماره ' . $invoice->number,
                'company_id' => auth()->user('company')->id,
            ]);
            $document = FinancialDocument::create([
                'document_number' => 'PAY-' . $invoice->number . '-' . now()->timestamp,
                'date' => now(),
                'description' => 'پرداخت فاکتور شماره ' . $invoice->number,
                'company_id' => auth()->user('company')->id,
            ]);
                // dd($document);
                // "reference" => "PAY-INV-19555-1740995863"
                //     "date" => Illuminate\Support\Carbon @1740995863 {#2928 ▶}
                //     "description" => "پرداخت فاکتور شماره INV-19555"
                //     "company_id" => 28
                //     "updated_at" => "2025-03-03 09:57:43"
                //     "created_at" => "2025-03-03 09:57:43"
                //     "id" => 25

                // dd([
                //     'financial_document_id' => $document->id,
                //     'account_id' => $person->account->id,
                //     'account_type' => Account::class,
                //     'debit' => $payment->amount,
                //     'credit' => 0,
                //     'description' => 'پرداخت فاکتور شماره ' . $invoice->number . ' به ' . $person->fullname,
                // ]);
                // array:6 [▼ // app\Models\Payment.php:54
                //     "financial_document_id" => 26
                //     "account_id" => 2
                //     "account_type" => "App\Models\Account"
                //     "debit" => 7000000000.0
                //     "credit" => 0
                //     "description" => "پرداخت فاکتور شماره INV-19555 به الیبلابا یبلیبلی"
                //     ]

            //     dd([
            //         'financial_document_id' => $document->id,
            //         'account_id' => $payment->paymentable_id,
            //         'account_type' => $payment->paymentable_type,
            //         'debit' => 0,
            //         'credit' => $payment->amount,
            //         'description' => 'کسر از ' . $payment->paymentable_type . ' برای پرداخت فاکتور ' . $invoice->number,
            //     ]);
            //     array:6 [▼ // app\Models\Payment.php:72
            //     "financial_document_id" => 27
            //     "account_id" => "2"
            //     "account_type" => "App\Models\CompanyBankAccount"
            //     "debit" => 0
            //     "credit" => 7000000000.0
            //     "description" => "کسر از App\Models\CompanyBankAccount برای پرداخت فاکتور INV-19555"
            //   ]
            // Transaction::create([
            //     'financial_document_id' => $document->id,
            //     'account_id' => $person->account->id,
            //     'account_type' => Account::class,
            //     'debit' => $payment->amount,
            //     'credit' => 0,
            //     'description' => 'پرداخت فاکتور شماره ' . $invoice->number . ' به ' . $person->fullname,
            // ]);

            // Transaction::create([
            //     'financial_document_id' => $document->id,
            //     'account_id' => $payment->paymentable_id,
            //     'account_type' => $payment->paymentable_type,
            //     'debit' => 0,
            //     'credit' => $payment->amount,
            //     'description' => 'کسر از ' . $payment->paymentable_type . ' برای پرداخت فاکتور ' . $invoice->number,
            // ]);
        });
    }
    public function payable()
    {
        return $this->morphTo();
    }
    public static function cleanNumber($value)
    {
        return (float) str_replace(',', '', $value ?? 0);
    }

    public function getCreatedAtJalaliAttribute()
    {
        return verta($this->created_at)->format('Y/m/d');
    }
}
