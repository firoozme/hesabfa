<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class ProductService
{
    /**
     * ایجاد محصول جدید.
     *
     * @param array $data
     * @param bool $withEvents
     * @return Product
     * @throws \Exception
     */
    public static function createProduct(array $data, bool $withEvents = true): Product
    {
        try {
            return DB::transaction(function () use ($data, $withEvents) {
                $method = $withEvents ? 'create' : 'createQuietly';
                $product = Product::$method([
                    'name' => $data['name'],
                    'barcode' => $data['barcode'] ?? null,
                    'selling_price' => (float) str_replace(',', '', $data['selling_price'] ?? 0),
                    'purchase_price' => (float) str_replace(',', '', $data['purchase_price'] ?? 0),
                    'minimum_order' => $data['minimum_order'] ?? 1,
                    'lead_time' => $data['lead_time'] ?? 1,
                    'reorder_point' => $data['reorder_point'] ?? 1,
                    'sales_tax' => $data['sales_tax'] ?? 0,
                    'purchase_tax' => $data['purchase_tax'] ?? 0,
                    'product_type_id' => $data['product_type_id'],
                    'inventory' => $data['inventory'] ?? 0,
                    'product_unit_id' => $data['product_unit_id'],
                    'tax_id' => $data['tax_id'] ?? null,
                    'product_category_id' => $data['product_category_id'],
                    'company_id' => auth('company')->user()->id,
                ]);

                if (!empty($data['image'])) {
                    $product->update(['image' => $data['image']]);
                }

                if (!empty($data['store_id']) && !empty($data['inventory']) && $data['inventory'] > 0) {
                    self::updateInventory($product, Store::findOrFail($data['store_id']), $data['inventory'], 'initial_stock', $withEvents);
                }

                Log::info("محصول {$product->id} با موفقیت ایجاد شد.", ['with_events' => $withEvents]);
                return $product;
            });
        } catch (\Exception $e) {
            Log::error("خطا در ایجاد محصول: " . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    /**
     * به‌روزرسانی محصول.
     *
     * @param Product $product
     * @param array $data
     * @param bool $withEvents
     * @return Product
     * @throws \Exception
     */
    public static function updateProduct(Product $product, array $data, bool $withEvents = true): Product
    {
        try {
            return DB::transaction(function () use ($product, $data, $withEvents) {
                $method = $withEvents ? 'update' : 'updateQuietly';
                $product->$method($data);

             

                Log::info("محصول {$product->id} با موفقیت به‌روزرسانی شد.", ['with_events' => $withEvents]);
                return $product;
            });
        } catch (\Exception $e) {
            Log::error("خطا در به‌روزرسانی محصول {$product->id}: " . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    /**
     * حذف نرم محصول.
     *
     * @param Product $product
     * @param bool $withEvents
     * @return void
     * @throws \Exception
     */
    public static function deleteProduct(Product $product, bool $withEvents = true): void
    {
        try {
            DB::transaction(function () use ($product, $withEvents) {
                // بررسی وابستگی‌ها (مثل فاکتورها)
                if ($product->invoices()->exists()) {
                    throw new \Exception("محصول {$product->name} در فاکتورها استفاده شده و نمی‌تواند حذف شود.");
                }

                // حذف موجودی‌های مرتبط در انبارها
                DB::table('product_store')
                    ->where('product_id', $product->id)
                    ->delete();

                // حذف نرم محصول
                $method = $withEvents ? 'delete' : 'deleteQuietly';
                $product->$method();

                Log::info("محصول {$product->id} با موفقیت حذف شد.", ['with_events' => $withEvents]);
            });
        } catch (\Exception $e) {
            Log::error("خطا در حذف محصول {$product->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * بازیابی محصول حذف‌شده.
     *
     * @param Product $product
     * @param bool $withEvents
     * @return Product
     * @throws \Exception
     */
    public static function restoreProduct(Product $product, bool $withEvents = true): Product
    {
        try {
            return DB::transaction(function () use ($product, $withEvents) {
                $method = $withEvents ? 'restore' : 'restoreQuietly';
                $product->$method();

                Log::info("محصول {$product->id} با موفقیت بازیابی شد.", ['with_events' => $withEvents]);
                return $product;
            });
        } catch (\Exception $e) {
            Log::error("خطا در بازیابی محصول {$product->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * به‌روزرسانی موجودی محصول در انبار.
     *
     * @param Product $product
     * @param Store $store
     * @param float $quantity
     * @param string $transactionType
     * @param bool $withEvents
     * @return void
     * @throws \Exception
     */
    public static function updateInventory(Product $product, Store $store, float $quantity, string $transactionType, bool $withEvents = true): void
    {
        try {
            DB::transaction(function () use ($product, $store, $quantity, $transactionType, $withEvents) {
                $pivot = DB::table('product_store')
                    ->where('product_id', $product->id)
                    ->where('store_id', $store->id)
                    ->first();

                $currentQuantity = $pivot ? $pivot->quantity : 0;
                $newQuantity = $currentQuantity;

                switch ($transactionType) {
                    case 'initial_stock':
                    case 'purchase':
                    case 'purchase_return_reversal':
                        $newQuantity += $quantity;
                        break;
                    case 'purchase_return':
                    case 'purchase_reversal':
                    case 'update_stock':
                        $newQuantity = $transactionType === 'update_stock' ? $currentQuantity + $quantity : $currentQuantity - $quantity;
                        break;
                    default:
                        throw new \Exception("نوع تراکنش نامعتبر: {$transactionType}");
                }

                if ($newQuantity < 0) {
                    throw new \Exception("موجودی انبار برای محصول {$product->name} در انبار {$store->title} نمی‌تواند منفی شود (موجودی کنونی: {$currentQuantity}, مقدار تغییر: {$quantity}).");
                }

                $method = $withEvents ? 'update' : 'updateQuietly';
                if ($pivot) {
                    DB::table('product_store')
                        ->where('product_id', $product->id)
                        ->where('store_id', $store->id)
                        ->$method(['quantity' => $newQuantity]);
                } else {
                    DB::table('product_store')->insert([
                        'product_id' => $product->id,
                        'store_id' => $store->id,
                        'quantity' => $newQuantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // به‌روزرسانی موجودی کلی محصول
                $totalInventory = DB::table('product_store')
                    ->where('product_id', $product->id)
                    ->sum('quantity');
                $product->$method(['inventory' => $totalInventory]);

                Log::info("موجودی محصول {$product->id} در انبار {$store->id} به‌روزرسانی شد. نوع تراکنش: {$transactionType}, مقدار: {$quantity}, موجودی جدید: {$newQuantity}", ['with_events' => $withEvents]);
            });
        } catch (\Exception $e) {
            Log::error("خطا در به‌روزرسانی موجودی محصول {$product->id} در انبار {$store->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * دریافت موجودی محصول در انبار.
     *
     * @param int $productId
     * @param int $storeId
     * @return float
     */
    public static function getStock(int $productId, int $storeId): float
    {
        $pivot = DB::table('product_store')
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->first();

        return $pivot ? $pivot->quantity : 0;
    }

    /**
     * دریافت لیست محصولات با فیلترهای اختیاری.
     *
     * @param array $filters
     * @param bool $withTrashed
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getProducts(array $filters = [], bool $withTrashed = false)
    {
        $query = Product::query()->where('company_id', auth('company')->user()->id);

        if ($withTrashed) {
            $query->withTrashed();
        }

        if (!empty($filters['product_type_id'])) {
            $query->where('product_type_id', $filters['product_type_id']);
        }

        if (!empty($filters['product_category_id'])) {
            $query->where('product_category_id', $filters['product_category_id']);
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        return $query->get();
    }
}