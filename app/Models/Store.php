<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Product;
use App\Traits\LogsActivity;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $guarded=[];

    public function getCreatedAtJalaliAttribute()
    {
        return verta($this->created_at)->format('Y/m/d');
    }
    public function getLocationAttribute()
    {
        return $this->latitude.','.$this->longitude;
    }
    public function storeTransactions()
    {
        return $this->hasMany(StoreTransaction::class)->whereNull('deleted_at');
    }
    public function products()
    {
        return $this->belongsToMany(Product::class, 'store_product')
                    ->withPivot('quantity');
    }

     // تعریف رابطه با StoreTransaction
     public function transactions()
     {
         return $this->hasMany(StoreTransaction::class, 'store_id')->whereNull('deleted_at');
     }
 
     public function getStock($productId)
     {
        // return 3000;
         $entries = $this->transactions()
             ->whereIn('type', ['entry','in'])
             ->whereHas('items', function ($query) use ($productId) {
                 $query->where('product_id', $productId);
             })
             ->with(['items' => function ($query) use ($productId) {
                 $query->where('product_id', $productId);
             }])
             ->get()
             ->sum(function ($transaction) {
                 return $transaction->items->sum('quantity');
             });
 
         $exits = $this->transactions()
             ->where('type', 'exit')
             ->whereHas('items', function ($query) use ($productId) {
                 $query->where('product_id', $productId);
             })
             ->with(['items' => function ($query) use ($productId) {
                 $query->where('product_id', $productId);
             }])
             ->get()
             ->sum(function ($transaction) {
                 return $transaction->items->sum('quantity');
             });
//  dd($entries, $exits);
         return $entries - $exits;
     }
//      public function getStock($productId)
// {
//     $entries = $this->transactions()
//         ->where('type', 'entry')
//         ->whereHas('items', fn($query) => $query->where('product_id', $productId))
//         ->with(['items' => fn($query) => $query->where('product_id', $productId)])
//         ->get()
//         ->sum(fn($transaction) => $transaction->items->sum('quantity'));

//     $exits = $this->transactions()
//         ->where('type', 'exit')
//         ->whereHas('items', fn($query) => $query->where('product_id', $productId))
//         ->with(['items' => fn($query) => $query->where('product_id', $productId)])
//         ->get()
//         ->sum(fn($transaction) => $transaction->items->sum('quantity'));

//     return $entries - $exits;
// }

      // تعداد کل محصولات وارد شده به انبار
    public function getTotalEntries()
    {
        return StoreTransactionItem::whereHas('storeTransaction', function ($query) {
                $query->where('store_id', $this->id)
                      ->where('type', 'entry');
            })
            ->sum('quantity');
    }

    // تعداد کل محصولات خارج شده از انبار
    public function getTotalExits()
    {
        return StoreTransactionItem::whereHas('storeTransaction', function ($query) {
                $query->where('store_id', $this->id)
                      ->where('type', 'exit');
            })
            ->sum('quantity');
    }

    // تعداد محصولات منحصربه‌فرد در انبار
    public function getUniqueProductsCount()
    {
        return $this->products()->distinct()->count();
    }

    // تعداد کل موجودی انبار (مجموع موجودی همه محصولات)
    public function getTotalInventory()
    {
        return $this->products()->sum('store_product.quantity');
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // تعداد تراکنش‌های ورودی
    public function getEntryTransactionsCount()
    {
        return $this->transactions()->where('type', 'entry')->count();
    }

    // تعداد تراکنش‌های خروجی
    public function getExitTransactionsCount()
    {
        return $this->transactions()->where('type', 'exit')->count();
    }

    public function isEmpty()
    {
        // چک می‌کنیم که آیا انبار هیچ محصولی با موجودی داره یا نه
        $totalStock = $this->transactions()
            ->whereIn('type', ['entry', 'exit'])
            ->with('items')
            ->get()
            ->flatMap->items
            ->groupBy('product_id')
            ->map(function ($items) {
                return $items->sum(function ($item) {
                    return $item->transaction->type === 'entry' ? $item->quantity : -$item->quantity;
                });
            })
            ->sum();

        return $totalStock <= 0;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($store) {
            // اگه این انبار پیش‌فرض شده، بقیه رو غیرفعال کن
            // if ($store->is_default) {
            //     static::where('company_id',auth()->user('company')->id)->where('id', '!=', $store->id)->update(['is_default' => false]);
            // }
        });
    }

}
