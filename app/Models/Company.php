<?php

namespace App\Models;

use App\Models\Plan;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Traits\LogsActivity;
use App\Models\CompanySetting;
use App\Models\SubscriptionPayment;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Model;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Company extends Authenticatable implements  HasName
{
    use LogsActivity;
    protected $guarded =[];


    public function getFilamentName(): string
    {
        return $this->fullname ?? $this->mobile; // or $this->name, depending on how you want to identify users
    }

    public function getCreatedAtJalaliAttribute(){
        return verta($this->created_at);
    }

    public function settings()
    {
        return $this->hasOne(CompanySetting::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($company) {
            $defaultPlan = Plan::getDefaultPlan();
            if ($defaultPlan) {
                $subscription = Subscription::create([
                    'company_id' => $company->id,
                    'plan_id' => $defaultPlan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($defaultPlan->duration),
                ]);
                Transaction::create([
                    'company_id' => $company->id,
                    'debit' => 0,
                    'credit' => 0,
                    'description' => "فعال‌سازی پلن رایگان {$defaultPlan->name}",
                    'transaction_type' => 'debit',
                    'account_type' => 'App\Models\Subscription',
                    'account_id' => $subscription->id,
                    'financial_document_id' => null, // یا مقدار مناسب
                    'transfer_id' => null, // یا مقدار مناسب
                ]);
            }
        });
    }
}
