<?php

namespace App\Models;

use App\Models\Income;
use App\Models\Account;
use App\Models\Expense;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class AccountingTransaction extends Model
{
    use LogsActivity;
    protected $guarded = [];
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function income()
    {
        return $this->belongsTo(Income::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
