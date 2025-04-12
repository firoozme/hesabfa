<?php

namespace App\Models;

use App\Models\Payment;
use App\Models\ExpenseItem;
use App\Traits\LogsActivity;
use App\Models\AccountingCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use LogsActivity;
    use SoftDeletes;
    protected $guarded=[];
    public function items()
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function updateTotalAmount()
    {
        $this->total_amount = $this->items->sum('amount');
        $this->save();
    }
    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }
    public function getDateJalaliAttribute(){
        return verta($this->date)->format('Y/m/d');
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }
    public function getTotalAmountAttribute()
    {
        return $this->items()->sum('amount');
    }
    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->total_paid; // مقدار باقی‌مانده برای پرداخت
    }
    public static function boot()
{
    parent::boot();

    static::created(function ($expense) {
        foreach ($expense->items as $item) {
            AccountingTransaction::create([
                'expense_id' => $expense->id,
                'account_id' => $item->account_id,
                'amount' => $item->amount,
                'date' => $expense->date,
            ]);
        }
    });

    static::updated(function ($expense) {
        if ($expense->isDirty('status') && $expense->status === 'paid') {
            // اینجا می‌تونید تراکنش‌های پرداخت رو به سیستم حسابداری اضافه کنید
            // مثلاً ثبت یه تراکنش برای حساب بانکی یا صندوق
        }
    });
}


}
