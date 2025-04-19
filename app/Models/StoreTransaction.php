<?php

namespace App\Models;

use App\Models\Store;
use App\Models\Account;
use App\Models\Transaction;
use App\Traits\LogsActivity;
use App\Models\AccountingDocument;
use App\Models\StoreTransactionItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreTransaction extends Model
{
    use LogsActivity;
    use SoftDeletes;
    protected static ?string $title = 'آمار موجودی انبار';


    protected $fillable = ['store_id', 'type', 'date', 'reference', 'destination_type', 'destination_id'];
        public function store()
    {
        return $this->belongsTo(Store::class);
    }


    public function destination()
    {
        return $this->morphTo();
    }
    public function items()
    {
        return $this->hasMany(StoreTransactionItem::class, 'store_transaction_id');
    }
    public function getDateJalaliAttribute()
    {
        return verta($this->date)->format('Y/m/d H:i');
    }
    protected static function booted()
    {
        static::created(function ($transaction) {
            // if ($transaction->type === 'exit' || $transaction->type === 'transfer') {
            //     $document = AccountingDocument::create([
            //         'reference' => 'DOC-' . $transaction->reference,
            //         'date' => $transaction->date,
            //         'description' => 'حواله ' . $transaction->type . ' - ' . $transaction->reference,
            //         'company_id' => $transaction->store->company_id,
            //     ]);

            //     $inventoryAccount = Account::where('code', '101')->first(); // فرض: حساب موجودی انبار
            //     $totalValue = $transaction->items->sum(fn($item) => $item->product->purchase_price * $item->quantity);

            //     // کاهش موجودی انبار مبدا
            //     Transaction::create([
            //         'accounting_document_id' => $document->id,
            //         'account_id' => $inventoryAccount->id,
            //         'account_type' => Account::class,
            //         'debit' => 0,
            //         'credit' => $totalValue,
            //         'description' => 'خروج از انبار ' . $transaction->store->title,
            //     ]);

            //     if ($transaction->type === 'transfer') {
            //         $destinationInventoryAccount = Account::where('code', '102')->first(); // فرض: حساب انبار مقصد
            //         Transaction::create([
            //             'accounting_document_id' => $document->id,
            //             'account_id' => $destinationInventoryAccount->id,
            //             'account_type' => Account::class,
            //             'debit' => $totalValue,
            //             'credit' => 0,
            //             'description' => 'ورود به انبار ' . $transaction->destination->title,
            //         ]);
            //     } elseif ($transaction->type === 'exit') {
            //         $expenseAccount = Account::where('code', '501')->first(); // فرض: حساب هزینه
            //         Transaction::create([
            //             'accounting_document_id' => $document->id,
            //             'account_id' => $expenseAccount->id,
            //             'account_type' => Account::class,
            //             'debit' => $totalValue,
            //             'credit' => 0,
            //             'description' => 'خروج به مقصد ' . ($transaction->destination_type === 'Customer' ? 'مشتری' : 'دیگر'),
            //         ]);
            //     }
            // }
        });
    }

}
