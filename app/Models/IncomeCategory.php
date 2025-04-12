<?php

namespace App\Models;

use App\Models\Account;
use App\Traits\LogsActivity;
use App\Models\IncomeCategory as IC;
use Illuminate\Database\Eloquent\Model;

class IncomeCategory extends Model
{
    use LogsActivity;
    protected $guarded=[];
    public function parent()
    {
        return $this->belongsTo(IC::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(IC::class, 'parent_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function category(){
        return $this->belongsTo(IC::class,'parent_id');
    }

}
