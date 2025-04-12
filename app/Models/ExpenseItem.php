<?php

namespace App\Models;

use App\Models\Account;
use App\Models\Expense;
use App\Traits\LogsActivity;
use App\Models\AccountingCategory;
use Illuminate\Database\Eloquent\Model;

class ExpenseItem extends Model
{
    use LogsActivity;
    protected $guarded = [];
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    public function category(){
        return $this->belongsTo(AccountingCategory::class,'accounting_category_id');
    }
    
}
