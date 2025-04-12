<?php

namespace App\Models;

use App\Models\Account;
use App\Traits\LogsActivity;
use App\Models\FinancialDocument;
use Illuminate\Database\Eloquent\Model;

class FinancialDocumentLine extends Model
{
    use LogsActivity;
    protected $guarded = [];

    public function document()
    {
        return $this->belongsTo(FinancialDocument::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
