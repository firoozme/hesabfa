<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use App\Models\AccountingCategory as AC;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingCategory extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = [
        'id'
    ];
    public function category(){
        return $this->belongsTo(AC::class,'parent_id');
    }
    // public function products(){
    //     return $this->hasMany(Product::class);
    // }
}
