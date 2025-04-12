<?php

namespace App\Models;

use App\Models\Income;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class IncomeReceipt extends Model
{
    use LogsActivity;
    protected $guarded = [];

    public function income()
    {
        return $this->belongsTo(Income::class);
    }

    public function receivable()
    {
        return $this->morphTo();
    }
}
