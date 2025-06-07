<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Transaction;
use App\Traits\LogsActivity;
use App\Models\FinancialDocumentLine;
use Illuminate\Database\Eloquent\Model;

class FinancialDocument extends Model
{
    use LogsActivity;
    protected $guarded = [];


    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'financial_document_id');
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function lines()
    {
        return $this->hasMany(FinancialDocumentLine::class);
    }
    public static function boot()
{
    parent::boot();

    static::saving(function ($document) {
        if ($document->status === 'posted') {
            $totalDebit = $document->lines->sum('debit');
            $totalCredit = $document->lines->sum('credit');
            if ($totalDebit != $totalCredit) {
                throw new \Exception('جمع بدهکار و بستانکار برابر نیست!');
            }
        }
    });
}
}
