<?php
namespace App\Models;

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Store;
use App\Models\Company;
use App\Models\Product;
use App\Models\PriceList;
use App\Models\ProductUnit;
use App\Traits\LogsActivity;
use App\Models\ProductCategory;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionItem;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\ActiveProductScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use LogsActivity;
    use SoftDeletes;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'barcode' => 'array',
        ];
    }
    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
    public function getCreatedAtJalaliAttribute()
    {
        return verta($this->created_at)->format('Y/m/d');
    }

    public function priceLists()
    {
        return $this->belongsToMany(PriceList::class);
    }
    public function atoreTransactions()
    {
        return $this->hasMany(StoreTransaction::class);
    }

    protected static function booted()
    {
        static::addGlobalScope(new ActiveProductScope);
    }
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_product')
            ->withPivot('quantity');
    }
    public function transactionItems()
    {
        return $this->hasMany(StoreTransactionItem::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    protected static function boot()
    {
        parent::boot();

        static::created(function ($product) {

            // dd($product->inventory, request()->input('selected_store_id') );
            // اگه موجودی اولیه داره
            // if ($product->inventory > 0) {
            //     $defaultStore = Store::where('is_default', true)->first();
            //     $selectedStoreId = request()->input('selected_store_id');

            //     $storeId = $defaultStore ? $defaultStore->id : ($selectedStoreId ?: null);
            //     if ($storeId) {
            //         // ثبت تراکنش
            //         $transaction = StoreTransaction::create([
            //             'store_id' => $storeId,
            //             'type' => 'in',
            //             'date' => Carbon::today(),
            //             'reference' => 'initial_stock_product_' . $product->id,
            //             'destination_type' => Product::class,
            //             'destination_id' => $product->id,
            //         ]);

            //         // ثبت آیتم تراکنش
            //         $transaction->items()->create([
            //             'product_id' => $product->id,
            //             'quantity' => $product->inventory,
            //         ]);

            //         // به‌روزرسانی موجودی در جدول store_product
            //         $product->stores()->syncWithoutDetaching([
            //             $storeId => ['quantity' => $product->inventory],
            //         ]);
            //     }
            // }
        });
    }
}
