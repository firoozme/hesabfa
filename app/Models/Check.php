<?php

namespace App\Models;

use App\Models\Company;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Check extends Model
{
    use LogsActivity;
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    const TYPE_RECEIVABLE = 'receivable';
    const TYPE_PAYABLE = 'payable';

    // ارتباط با بانک
    // public function bank()
    // {
    //     return $this->belongsTo(Bank::class);
    // }

    // ارتباط با پرداخت کننده
    // public function payer()
    // {
    //     return $this->belongsTo(Payer::class);
    // }

    // ارتباط با شعبه
    // public function branch()
    // {
    //     return $this->belongsTo(Branch::class);
    // }

    // وضعیت چک
    public function getStatusLabelAttribute()
    {
        $statusLabels = [
            'overdue' => 'سررسید گذشته',
            'in_progress' => 'در جریان وصول',
            'received' => 'وصول شده',
            'returned' => 'عودت شده',
            'cashed' => 'خرج شده',
        ];

        return $statusLabels[$this->status] ?? 'نامشخص';
    }

    // تاریخ‌های جلالی
    public function getDateReceivedJalaliAttribute()
    {
        return verta($this->date_received)->format('Y/m/d');
    }

    public function getDueDateJalaliAttribute()
    {
        return verta($this->due_date)->format('Y/m/d');
    }

    // رابطه با شرکت
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // رابطه پلی‌مورفیک
    public function checkable()
    {
        return $this->morphTo();
    }
}
