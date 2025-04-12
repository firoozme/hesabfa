<?php

namespace App\Models;

use App\Models\Payment;
use App\Models\Transfer;
use App\Traits\HasBalance;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCash extends Model
{
    use LogsActivity;
    use SoftDeletes, HasBalance;
    protected $guarded = [];

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

    public function getBalanceAttribute()
    {
        $debits = $this->incomingTransfers()->sum('amount');
        $credits = $this->outgoingTransfers()->sum('amount');
        return $debits - $credits;
    }
    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
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
