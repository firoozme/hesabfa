<?php
namespace App\Models;

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Store;
use App\Models\Company;
use App\Models\Product;
use App\Models\Discount;
use App\Models\PriceList;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\StoreProduct;
use App\Traits\LogsActivity;
use App\Models\ProductCategory;
use App\Models\StoreTransaction;
use Illuminate\Support\Facades\Log;
use App\Models\StoreTransactionItem;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Models\Scopes\ActiveProductScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use LogsActivity;
    use SoftDeletes;
    protected $guarded = [];
    protected static $updating = false; // فلگ برای جلوگیری از حلقه بی‌نهایت
    protected function casts(): array
    {
        return [
            'barcode'        => 'array',
            'selling_price'  => 'integer',
            'purchase_price' => 'integer',
        ];
    }
    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id')->where('company_id',auth('company')->user()->id);
    }
    public function type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
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

        // بررسی پس از ایجاد محصول
        static::created(function ($product) {
            \Log::warning('Product created1: inventory'.$product);
            // if Import Porduct with Excel
            if ($product->method == 'import') {
                $storeId   = $product->temp;
                $productId = $product->id;
                $quantity  = $product->inventory;
                // DB::table('store_product')->insert([
                //     'store_id' => $storeId,
                //     'product_id' => $productId,
                //     'quantity' => $quantity,
                // ]);
                $transaction = StoreTransaction::create([
                    'store_id'         => $storeId,
                    'type'             => 'entry',
                    'date'             => Carbon::today(),
                    'reference'        => 'INIT-' . $productId,
                    'destination_type' => Product::class,
                    'destination_id'   => $productId,
                ]);

                // ثبت آیتم تراکنش
                StoreTransactionItem::create([
                    'store_transaction_id' => $transaction->id,
                    'product_id'           => $productId,
                    'quantity'             => $quantity,

                ]);

                
            }
            \Log::warning('Product Inventory Set');
            // به‌روزرسانی inventory
            $product->updateInventory();

           
        });
        
        static::updated(function ($product) {
            // اگر در حال به‌روزرسانی هستیم، از اجرای دوباره جلوگیری کن
            if (static::$updating) {
                return;
            }

            if ($product->isDirty('inventory')) {
                static::$updating = true; // فعال کردن فلگ
                Log::alert("Product Updated");
                try {
                    $quantity = $product->inventory;

                    // به‌روزرسانی StoreTransactionItem
                    $storeTransactionItem = StoreTransactionItem::where('product_id', $product->id)->first();
                    if ($storeTransactionItem) {
                        $storeTransactionItem->update([
                            'quantity' => $quantity,
                        ]);
                    }
                    // به‌روزرسانی StoreProduct
                    $storeProduct = StoreProduct::where('product_id', $product->id)->first();
                    
                    if ($storeProduct) {
                        $storeProduct->update([
                            'quantity' => $quantity,
                        ]);
                    }
                    // $product->update([
                    //     'inventory' => $quantity
                    // ]);
                    Product::withoutEvents(function () use ($product, $quantity) {
                        $product->update(['inventory' => $quantity]);
                    });
                } finally {
                    static::$updating = false; // غیرفعال کردن فلگ
                }
            }

            \Log::warning('Product Inventory Set');
            // به‌روزرسانی inventory
            $product->updateInventory();
        });

        // بررسی قبل از حذف محصول
        static::deleting(function ($product) {
            // بررسی استفاده محصول در جدول invoice_items
            if ($product->invoiceItems()->exists()) {
                Notification::make()
                    ->title('خطا')
                    ->body('این محصول در فاکتورها استفاده شده است و نمی‌توان آن را حذف کرد.')
                    ->danger()
                    ->send();
                return false; // جلوگیری از حذف
            }

            // اگر محصول در هیچ فاکتوری استفاده نشده باشد، حذف نرم انجام می‌شود
            Log::info("محصول {$product->name} با موفقیت حذف شد.", [
                'product_id' => $product->id,
            ]);
        });
    }
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'product_id');
    }
    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function getDiscountedPriceAttribute()
    {
        if ($this->discount && $this->discount->isActive()) {
            if ($this->discount->type === 'percentage') {
                return (int)intval(str_replace(',', '', $this->selling_price)) * (1 - $this->discount->value / 100);
            } else {
                // dd((int)$this->discount->value);
                $discount_value = (float) str_replace(',', '', $this->discount->value);
                $selling_price = (float) str_replace(',', '', $this->selling_price);
                return max(0, $selling_price - $discount_value);
            }
        }

        return $this->selling_price;
    }

    public function setSellingPriceAttribute($value)
    {
        $this->attributes['selling_price'] = intval(str_replace(',', '', $value));
    }
    public function setPurchasePriceAttribute($value)
    {
        $this->attributes['purchase_price'] = intval(str_replace(',', '', $value));
    }
    public function getSellingPriceAttribute($value)
    {
        return number_format($value);
    }
    public function getPurchasePriceAttribute($value)
    {
        return number_format($value);

    }

    public function getRealInventoryAttribute(){
        $count = StoreProduct::where('product_id', $this->id)->sum('quantity');
        return $count;
    }

    public function updateInventory()
    {
        if (static::$updating) {
            return;
        }

        static::$updating = true;
        try {
            $totalQuantity = $this->getRealInventoryAttribute();
            $this->update(['inventory' => $totalQuantity]);
        } finally {
            static::$updating = false;
        }
    }
}
