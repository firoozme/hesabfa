<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Transaction;
use App\Traits\LogsActivity;
use App\Models\StoreTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingDocument extends Model
{
    use LogsActivity;
    use SoftDeletes;

    /**
     * فیلدهای قابل پر کردن
     * این‌ها ستون‌هایی هستن که توی جدول accounting_documents داریم
     */
    protected $fillable = ['reference', 'date', 'description', 'company_id'];

    /**
     * رابطه با مدل Company
     * هر سند متعلق به یه شرکت خاصه
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * رابطه با مدل Transaction
     * هر سند می‌تونه چندین تراکنش داشته باشه
     */
    public function transactions()
    {
        return $this->hasMany(StoreTransaction::class);
    }
}
