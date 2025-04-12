<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Payment;
use App\Models\Transfer;
use App\Traits\HasBalance;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fund extends Model
{
    use LogsActivity;
    use SoftDeletes, HasBalance;
    protected $guarded =[];
    public function getCreatedAtJalaliAttribute()
    {
        return verta($this->created_at)->format('Y/m/d');
    }

    public function outgoingTransfers()
    {
        return $this->morphMany(Transfer::class, 'source');
    }

    public function incomingTransfers()
    {
        return $this->morphMany(Transfer::class, 'destination');
    }
    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    // public function decreaseBalance($amount)
    // {
    //     if ($this->balance < $amount) {
    //         throw new \Exception("موجودی کافی نیست!");
    //     }

    //     $this->balance -= $amount;
    //     $this->save();
    // }
}
