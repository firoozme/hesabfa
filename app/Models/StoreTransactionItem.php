<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class StoreTransactionItem extends Model
{
    
    use LogsActivity;
    protected $guarded = [];

    public function transaction()
    {
        return $this->belongsTo(StoreTransaction::class, 'store_transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function storeTransaction()
    {
        return $this->belongsTo(StoreTransaction::class, 'store_transaction_id');
    }
    protected static function booted()
    {
        static::created(function ($item) {
            // dd($item);
            $product = $item->product;
            $transaction = $item->transaction;
            $store = $transaction->store;

            // آپدیت موجودی انبار
            $pivot = $store->products()->where('product_id', $product->id)->first();
            if (!$pivot) {
                $store->products()->attach($product->id, ['quantity' => 0]);
                $pivot = $store->products()->where('product_id', $product->id)->first();
            }

            if ($transaction->type === 'entry') {
                $pivot->pivot->quantity += $item->quantity;
                $product->inventory += $item->quantity;
            } elseif ($transaction->type === 'exit') {
                $pivot->pivot->quantity -= $item->quantity;
                $product->inventory -= $item->quantity;
            }
            $pivot->pivot->save();
            $product->save();
        });

        static::updated(function ($item) {
            $product = $item->product;
            $transaction = $item->transaction;
            $store = $transaction->store;
            $originalQuantity = $item->getOriginal('quantity');

            $pivot = $store->products()->where('product_id', $product->id)->first();

            if ($transaction->type === 'entry') {
                $pivot->pivot->quantity -= $originalQuantity;
                $pivot->pivot->quantity += $item->quantity;
                $product->inventory -= $originalQuantity;
                $product->inventory += $item->quantity;
            } elseif ($transaction->type === 'exit') {
                $pivot->pivot->quantity += $originalQuantity;
                $pivot->pivot->quantity -= $item->quantity;
                $product->inventory += $originalQuantity;
                $product->inventory -= $item->quantity;
            }
            $pivot->pivot->save();
            $product->save();
        });

        static::deleted(function ($item) {
            $product = $item->product;
            $transaction = $item->transaction;
            $store = $transaction->store;
            $pivot = $store->products()->where('product_id', $product->id)->first();

            if ($transaction->type === 'entry') {
                $pivot->pivot->quantity -= $item->quantity;
                $product->inventory -= $item->quantity;
            } elseif ($transaction->type === 'exit') {
                $pivot->pivot->quantity += $item->quantity;
                $product->inventory += $item->quantity;
            }
            $pivot->pivot->save();
            $product->save();
        });
    }
}
