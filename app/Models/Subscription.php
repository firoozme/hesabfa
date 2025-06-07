<?php

namespace App\Models;

use App\Models\Plan;
use App\Models\Company;
use App\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['company_id', 'plan_id', 'status', 'starts_at', 'ends_at', 'renewed_at'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'renewed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($subscription) {
            if ($subscription->plan->price == 0) {
                $subscription->update([
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($subscription->plan->duration),
                ]);
            }
        });
    }
    public function getStartsAtJalaliAttribute()
    {
        return verta($this->starts_at)->format('Y/m/d');
    }
    public function getEndsAtJalaliAttribute()
    {
        return verta($this->ends_at)->format('Y/m/d');
    }
}
