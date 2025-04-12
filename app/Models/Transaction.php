<?php

namespace App\Models;

use App\Models\Transfer;
use App\Traits\LogsActivity;
use App\Models\AccountingDocument;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use LogsActivity;
    protected $guarded = [];

    public function account()
    {
        return $this->morphTo();
    }
    public function document()
    {
        return $this->belongsTo(AccountingDocument::class, 'accounting_document_id');
    }
    public function financialDocument()
    {
        return $this->belongsTo(FinancialDocument::class, 'financial_document_id');
    }
    public function transfer()
    {
        return $this->belongsTo(Transfer::class, 'transfer_id');
    }
}
