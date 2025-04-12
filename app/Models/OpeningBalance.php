<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Transaction;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class OpeningBalance extends Model
{
    use LogsActivity;
    protected $guarded = [];

    public function getDateJalaliAttribute(){
        return verta($this->date);
    }
    public function accountable()
    {
        return $this->morphTo();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }
}
