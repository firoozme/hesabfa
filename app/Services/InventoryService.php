<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * به‌روزرسانی موجودی انبار برای یک محصول.
     *
     * @param Product $product
     * @param Store $store
     * @param float $quantity
     * @param string $transactionType
     * @return void
     * @throws \Exception
     */
    public static function updateInventory(Product $product, Store $store, int $quantity, string $type): void
    {
        Log::info([
            $product->id,
            $store->id, 
            $quantity,
            $type,
        ]);
        DB::transaction(function () use ($product, $store, $quantity, $type) {
            $pivot = $store->products()->find($product->id);
            if (!$pivot) {
                $store->products()->attach($product->id, ['quantity' => 0]);
                $pivot = $store->products()->find($product->id);
            }

            Log::info([$pivot->pivot->quantity, $product->inventory, $quantity]);

            if ($type === 'entry') {
                $pivot->pivot->quantity = abs($pivot->pivot->quantity) + $quantity;
                $product->inventory = abs($product->inventory) +  $quantity;
            } elseif ($type === 'exit') {
                if ($product->inventory < $quantity) {
                    throw new \InvalidArgumentException("موجودی کافی برای محصول {$product->id} وجود ندارد.");
                }
                $pivot->pivot->quantity = abs($pivot->pivot->quantity) - $quantity;
                $product->inventory = abs($product->inventory) -  $quantity;
            }elseif($type === 'equel'){
                $pivot->pivot->quantity = $quantity;
                $product->inventory = $quantity;
            }elseif($type === 'nochange'){
                $pivot->pivot->quantity = $product->inventory;
                $product->inventory = $product->inventory;
            } else {
                throw new \InvalidArgumentException("نوع تراکنش نامعتبر است: {$type}");
            }

            $pivot->pivot->save();
            $product->saveQuietly();
        });
    }


    /**
     * دریافت موجودی کنونی محصول در انبار.
     *
     * @param int $productId
     * @param int $storeId
     * @return float
     */
    public static function getStock(int $productId, int $storeId): float
    {
        $pivot = DB::table('store_product')
            ->where('product_id', $productId)
            ->where('store_id', $storeId)
            ->first();

        return $pivot ? $pivot->quantity : 0;
    }
}