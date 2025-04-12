<?php

namespace App\Models;

use App\Models\Installment;
use App\Traits\LogsActivity;
use App\Models\IncomeReceipt;
use App\Models\IncomeCategory;
use App\Models\AccountingTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Income extends Model
{
    use LogsActivity;
    use SoftDeletes;
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(IncomeCategory::class,'income_category_id');
    }

    public function receipts()
    {
        return $this->hasMany(IncomeReceipt::class);
    }
    // public function items()
    // {
    //     return $this->hasMany(IncomeReceipt::class);
    // }

    public function getTotalReceivedAttribute()
    {
        return $this->receipts->sum('amount');
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->total_received;
    }
    public function installments()
    {
        return $this->hasMany(Installment::class, 'invoice_id', 'invoice_id');
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($income) {
            AccountingTransaction::create([
                'income_id' => $income->id,
                'account_id' => $income->category->account_id ?? null,
                'amount' => $income->amount,
                'date' => now(),
            ]);
        });

        static::updated(function ($income) {
            if ($income->isDirty('status') && $income->status === 'received') {
                // می‌تونید تراکنش‌های اضافی برای دریافت ثبت کنید
            }
        });
    }
}
