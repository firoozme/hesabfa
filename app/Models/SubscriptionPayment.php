<?php

namespace App\Models;

use App\Models\Account;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Traits\LogsActivity;
use App\Models\FinancialDocument;
use App\Models\AccountingDocument;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function paymentable()
    {
        return $this->morphTo();
    }

    protected static function booted()
    {
        static::created(function ($payment) {
            if ($payment->status === 'successful') {
                $subscription = $payment->subscription;
                $company = $payment->company;

                $accountingDocument = AccountingDocument::create([
                    'reference' => 'SUB-PAY-' . $subscription->id . '-' . now()->timestamp,
                    'date' => now(),
                    'description' => 'پرداخت اشتراک شماره ' . $subscription->id,
                    'company_id' => $company->id,
                ]);

                $financialDocument = FinancialDocument::create([
                    'document_number' => 'SUB-PAY-' . $subscription->id . '-' . now()->timestamp,
                    'date' => now(),
                    'description' => 'پرداخت اشتراک شماره ' . $subscription->id,
                    'company_id' => $company->id,
                ]);

                Transaction::create([
                    'financial_document_id' => $financialDocument->id,
                    'account_id' => $company->account->id,
                    'account_type' => Account::class,
                    'debit' => $payment->amount,
                    'credit' => 0,
                    'description' => 'پرداخت اشتراک شماره ' . $subscription->id,
                ]);

                Transaction::create([
                    'financial_document_id' => $financialDocument->id,
                    'account_id' => $payment->paymentable_id,
                    'account_type' => $payment->paymentable_type,
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'description' => 'کسر از ' . $payment->paymentable_type . ' برای اشتراک ' . $subscription->id,
                ]);

                $subscription->update([
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($subscription->plan->duration),
                    'renewed_at' => now(),
                ]);
            }
        });
    }

    public function getCreatedAtJalaliAttribute()
    {
        return verta($this->created_at)->format('Y/m/d');
    }


}
