<?php

namespace App\Models;

use App\Models\Invoice;
use App\Traits\LogsActivity;
use App\Models\IncomeReceipt;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use LogsActivity;
    protected $guarded=[];
    public function installmentSale()
    {
        return $this->belongsTo(InstallmentSale::class);
    }

    public function income()
    {
        return $this->belongsTo(Income::class);
    }
    public function getDueDateJalaliAttribute()
    {
        return verta($this->due_date)->format('Y/m/d');
    }

}
