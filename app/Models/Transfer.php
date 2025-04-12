<?php

namespace App\Models;

use App\Models\Transaction;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transfer extends Model
{
    use LogsActivity;
    use SoftDeletes;
    protected $guarded = [];

    public function source()
    {
        return $this->morphTo();
    }

    public function destination()
    {
        return $this->morphTo();
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($transfer) {
            $transfer->createLedgerEntries();
        });
    }

    public function financialDocument()
    {
        return $this->belongsTo(FinancialDocument::class);
    }

    public function createLedgerEntries()
    {
        DB::transaction(function () {
            // محاسبه موجودی حساب مبدأ
            $sourceBalance = $this->getAccountBalance($this->source_type, $this->source_id);

            // // چک کردن موجودی
            // if ($sourceBalance < $this->amount) {
            //     throw new \Exception("موجودی حساب مبدأ کافی نیست. موجودی فعلی: " . number_format($sourceBalance));
            // }

            // ثبت تراکنش برای حساب مبدأ (کم کردن)
            Transaction::create([
                'transfer_id' => $this->id,
                'account_type' => $this->source_type,
                'account_id' => $this->source_id,
                'credit' => $this->amount,
                'debit' => 0,
                'description' => 'انتقال از حساب مبدأ: ' . $this->description,
            ]);

            // ثبت تراکنش برای حساب مقصد (اضافه کردن)
            Transaction::create([
                'transfer_id' => $this->id,
                'account_type' => $this->destination_type,
                'account_id' => $this->destination_id,
                'debit' => $this->amount,
                'credit' => 0,
                'description' => 'انتقال به حساب مقصد: ' . $this->description,
            ]);
        });
    }

    // محاسبه موجودی حساب از transactions
    public function getAccountBalance($type, $id)
    {
        $debits = Transaction::where('account_type', $type)
            ->where('account_id', $id)
            ->sum('debit');
        $credits = Transaction::where('account_type', $type)
            ->where('account_id', $id)
            ->sum('credit');
        return $debits - $credits;
    }

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = str_replace(',', '', $value);
    }

    public function getTransferDateJalaliAttribute()
    {
        return verta($this->transfer_date)->format('Y/m/d H:m');
    }

    public function getCreatedAtJalaliAttribute()
    {
        return verta($this->created_at)->format('J j D d');
    }
}