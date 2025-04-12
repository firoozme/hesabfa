<?php

namespace App\Models;

use App\Models\Person;
use App\Models\Account;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Product;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Traits\LogsActivity;
use App\Models\StoreTransaction;
use App\Models\AccountingDocument;
use Illuminate\Support\Facades\Log;
use App\Models\StoreTransactionItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use LogsActivity;
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->withTrashed();
    }

    // public function payments()
    // {
    //     return $this->hasMany(Payment::class);
    // }
    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }
    public function company()
    {
        return $this->belongsto(Company::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function getDateJalaliAttribute()
    {
        return verta($this->date)->format('Y/m/d');
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'transfer_id'); // فاکتور به تراکنش‌ها متصل است
    }
    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
    public function store()
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }
    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }
    public function getTotalAmountAttribute()
    {
        return $this->items()->sum('total_price');
    }
    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }
     // رابطه پلی‌مورفیک با چک‌ها
     public function checks()
     {
         return $this->morphMany(Check::class, 'checkable');
     }
     public function getSupplierAttribute()
        {
            if ($this->person->types()->where('title', 'تأمین کننده')->exists()) {
                return $this->person;
            }
            return null;
        }
    public function getFullNameAttribute(){
        $name = $this->number;
        if($this->title){
            $name .= $this->title;
        }
        return $name;
    }

    public function installmentSale()
    {
        return $this->hasOne(InstallmentSale::class);
    }

    public function getIsInstallmentAttribute()
    {
        return $this->installmentSale !== null;
    }

    public function getPaidAmountAttribute()
    {

        if($this->type == 'sale'){
            if (!$this->is_installment) {
                return 0; // یا منطق پرداخت نقدی اگه دارید
            }
    
            $installmentSale = $this->installmentSale;
            $prepayment = $installmentSale->prepayment;
            $paidInstallments = $installmentSale->installments()->where('status', 'paid')->sum('amount');
    
            return $prepayment + $paidInstallments;
            
        }else{
            return $this->payments()->sum('amount');

        }
    }


// protected static function booted()
// {
//     // ایجاد فاکتور (خرید و برگشت خرید)
//     static::created(function ($invoice) {
//         if ($invoice->type === 'purchase') {
//             if (!$invoice->store || $invoice->store->trashed()) {
//                 throw new \Exception("انبار مرتبط با فاکتور {$invoice->number} وجود ندارد یا حذف شده است.");
//             }

//             $transaction = StoreTransaction::create([
//                 'store_id' => $invoice->store_id,
//                 'type' => 'entry',
//                 'date' => now(),
//                 'reference' => 'INV-' . $invoice->number,
//             ]);

//             foreach ($invoice->items as $item) {
//                 StoreTransactionItem::create([
//                     'store_transaction_id' => $transaction->id,
//                     'product_id' => $item->product_id,
//                     'quantity' => $item->quantity,
//                 ]);
//             }

//             Log::info("فاکتور خرید شماره {$invoice->number} با موفقیت ایجاد شد.", [
//                 'invoice_id' => $invoice->id,
//                 'store_id' => $invoice->store_id,
//             ]);
//         } elseif ($invoice->type === 'purchase_return') {
//             if (!$invoice->store || $invoice->store->trashed()) {
//                 throw new \Exception("انبار مرتبط با فاکتور برگشت {$invoice->number} وجود ندارد یا حذف شده است.");
//             }

//             $transaction = StoreTransaction::create([
//                 'store_id' => $invoice->store_id,
//                 'type' => 'exit',
//                 'date' => now(),
//                 'reference' => 'RET-INV-' . $invoice->number,
//             ]);

//             foreach ($invoice->items as $item) {
//                 $currentStock = $invoice->store->getStock($item->product_id);
//                 if ($currentStock < $item->quantity) {
//                     throw new \Exception("موجودی کافی برای محصول {$item->product->name} در انبار وجود ندارد.");
//                 }

//                 StoreTransactionItem::create([
//                     'store_transaction_id' => $transaction->id,
//                     'product_id' => $item->product_id,
//                     'quantity' => $item->quantity,
//                 ]);
//             }

//             Log::info("فاکتور برگشت خرید شماره {$invoice->number} با موفقیت ایجاد شد.", [
//                 'invoice_id' => $invoice->id,
//                 'store_id' => $invoice->store_id,
//             ]);
//         }
//     });

//     // ویرایش فاکتور (خرید و برگشت خرید)
//     static::updated(function ($invoice) {
//         if ($invoice->type === 'purchase') {
//             $storeTransaction = StoreTransaction::where('reference', 'INV-' . $invoice->number)->first();
//             if ($storeTransaction) {
//                 $originalItems = InvoiceItem::where('invoice_id', $invoice->id)
//                     ->whereNull('deleted_at')
//                     ->get()
//                     ->keyBy('product_id');

//                 $newItems = $invoice->items->keyBy('product_id');

//                 foreach ($newItems as $productId => $item) {
//                     $originalItem = $originalItems->get($productId);
//                     $originalQuantity = $originalItem ? $originalItem->quantity : 0;
//                     $newQuantity = $item->quantity;

//                     $quantityDiff = $newQuantity - $originalQuantity;

//                     if ($quantityDiff != 0) {
//                         if (!$invoice->store || $invoice->store->trashed()) {
//                             throw new \Exception("انبار مرتبط با فاکتور {$invoice->number} وجود ندارد یا حذف شده است.");
//                         }

//                         $currentStock = $invoice->store->getStock($item->product_id);

//                         if ($quantityDiff > 0) {
//                             $transactionType = 'entry';
//                             $quantityToApply = $quantityDiff;
//                         } elseif ($quantityDiff < 0) {
//                             $quantityToRemove = abs($quantityDiff);
//                             if ($currentStock < $quantityToRemove) {
//                                 throw new \Exception("موجودی کافی برای محصول {$item->product->name} در انبار وجود ندارد.");
//                             }
//                             $transactionType = 'exit';
//                             $quantityToApply = $quantityToRemove;
//                         }

//                         $uniqueReference = 'EDIT-INV-' . $invoice->number . '-' . $transactionType . '-' . now()->timestamp;

//                         $newTransaction = StoreTransaction::create([
//                             'store_id' => $invoice->store_id,
//                             'type' => $transactionType,
//                             'date' => now(),
//                             'reference' => $uniqueReference,
//                         ]);

//                         StoreTransactionItem::create([
//                             'store_transaction_id' => $newTransaction->id,
//                             'product_id' => $item->product_id,
//                             'quantity' => $quantityToApply,
//                         ]);
//                     }

//                     StoreTransactionItem::updateOrCreate(
//                         [
//                             'store_transaction_id' => $storeTransaction->id,
//                             'product_id' => $item->product_id,
//                         ],
//                         [
//                             'quantity' => $newQuantity,
//                         ]
//                     );
//                 }

//                 $originalProductIds = $originalItems->pluck('product_id')->toArray();
//                 $newProductIds = $newItems->pluck('product_id')->toArray();
//                 $deletedProductIds = array_diff($originalProductIds, $newProductIds);

//                 foreach ($deletedProductIds as $productId) {
//                     $originalItem = $originalItems->get($productId);
//                     $quantityToRemove = $originalItem->quantity;

//                     if (!$invoice->store || $invoice->store->trashed()) {
//                         throw new \Exception("انبار مرتبط با فاکتور {$invoice->number} وجود ندارد یا حذف شده است.");
//                     }

//                     $currentStock = $invoice->store->getStock($productId);

//                     if ($currentStock < $quantityToRemove) {
//                         throw new \Exception("موجودی کافی برای محصول حذف‌شده در انبار وجود ندارد.");
//                     }

//                     $uniqueReference = 'EDIT-INV-' . $invoice->number . '-DELETE-' . now()->timestamp;

//                     $exitTransaction = StoreTransaction::create([
//                         'store_id' => $invoice->store_id,
//                         'type' => 'exit',
//                         'date' => now(),
//                         'reference' => $uniqueReference,
//                     ]);

//                     StoreTransactionItem::create([
//                         'store_transaction_id' => $exitTransaction->id,
//                         'product_id' => $productId,
//                         'quantity' => $quantityToRemove,
//                     ]);

//                     StoreTransactionItem::where('store_transaction_id', $storeTransaction->id)
//                         ->where('product_id', $productId)
//                         ->delete();
//                 }

//                 Log::info("فاکتور خرید شماره {$invoice->number} با موفقیت ویرایش شد.", [
//                     'invoice_id' => $invoice->id,
//                     'store_id' => $invoice->store_id,
//                     'items' => $invoice->items->toArray(),
//                 ]);
//             }
//         } elseif ($invoice->type === 'purchase_return') {
//             $storeTransaction = StoreTransaction::where('reference', 'RET-INV-' . $invoice->number)->first();
//             if ($storeTransaction) {
//                 $originalItems = InvoiceItem::where('invoice_id', $invoice->id)
//                     ->whereNull('deleted_at')
//                     ->get()
//                     ->keyBy('product_id');

//                 $newItems = $invoice->items->keyBy('product_id');

//                 foreach ($newItems as $productId => $item) {
//                     $originalItem = $originalItems->get($productId);
//                     $originalQuantity = $originalItem ? $originalItem->quantity : 0;
//                     $newQuantity = $item->quantity;

//                     $quantityDiff = $newQuantity - $originalQuantity;

//                     if ($quantityDiff != 0) {
//                         if (!$invoice->store || $invoice->store->trashed()) {
//                             throw new \Exception("انبار مرتبط با فاکتور برگشت {$invoice->number} وجود ندارد یا حذف شده است.");
//                         }

//                         $currentStock = $invoice->store->getStock($item->product_id);

//                         if ($quantityDiff > 0) {
//                             $quantityToRemove = $quantityDiff;
//                             if ($currentStock < $quantityToRemove) {
//                                 throw new \Exception("موجودی کافی برای محصول {$item->product->name} در انبار وجود ندارد.");
//                             }
//                             $transactionType = 'exit';
//                             $quantityToApply = $quantityToRemove;
//                         } elseif ($quantityDiff < 0) {
//                             $quantityToAdd = abs($quantityDiff);
//                             $transactionType = 'entry';
//                             $quantityToApply = $quantityToAdd;
//                         }

//                         $uniqueReference = 'EDIT-RET-INV-' . $invoice->number . '-' . $transactionType . '-' . now()->timestamp;

//                         $newTransaction = StoreTransaction::create([
//                             'store_id' => $invoice->store_id,
//                             'type' => $transactionType,
//                             'date' => now(),
//                             'reference' => $uniqueReference,
//                         ]);

//                         StoreTransactionItem::create([
//                             'store_transaction_id' => $newTransaction->id,
//                             'product_id' => $item->product_id,
//                             'quantity' => $quantityToApply,
//                         ]);
//                     }

//                     StoreTransactionItem::updateOrCreate(
//                         [
//                             'store_transaction_id' => $storeTransaction->id,
//                             'product_id' => $item->product_id,
//                         ],
//                         [
//                             'quantity' => $newQuantity,
//                         ]
//                     );
//                 }

//                 $originalProductIds = $originalItems->pluck('product_id')->toArray();
//                 $newProductIds = $newItems->pluck('product_id')->toArray();
//                 $deletedProductIds = array_diff($originalProductIds, $newProductIds);

//                 foreach ($deletedProductIds as $productId) {
//                     $originalItem = $originalItems->get($productId);
//                     $quantityToAdd = $originalItem->quantity;

//                     if (!$invoice->store || $invoice->store->trashed()) {
//                         throw new \Exception("انبار مرتبط با فاکتور برگشت {$invoice->number} وجود ندارد یا حذف شده است.");
//                     }

//                     $uniqueReference = 'EDIT-RET-INV-' . $invoice->number . '-DELETE-' . now()->timestamp;

//                     $entryTransaction = StoreTransaction::create([
//                         'store_id' => $invoice->store_id,
//                         'type' => 'entry',
//                         'date' => now(),
//                         'reference' => $uniqueReference,
//                     ]);

//                     StoreTransactionItem::create([
//                         'store_transaction_id' => $entryTransaction->id,
//                         'product_id' => $productId,
//                         'quantity' => $quantityToAdd,
//                     ]);

//                     StoreTransactionItem::where('store_transaction_id', $storeTransaction->id)
//                         ->where('product_id' , $productId)
//                         ->delete();
//                 }

//                 Log::info("فاکتور برگشت خرید شماره {$invoice->number} با موفقیت ویرایش شد.", [
//                     'invoice_id' => $invoice->id,
//                     'store_id' => $invoice->store_id,
//                     'items' => $invoice->items->toArray(),
//                 ]);
//             }
//         }
//         elseif ($invoice->type === 'sale') {
//             $storeTransaction = StoreTransaction::where('reference', 'SALE-INV-' . $invoice->number)->first();
//             if ($storeTransaction) {
//                 $originalItems = InvoiceItem::where('invoice_id', $invoice->id)
//                     ->whereNull('deleted_at')
//                     ->get()
//                     ->keyBy('product_id');

//                 $newItems = $invoice->items->keyBy('product_id');

//                 foreach ($newItems as $productId => $item) {
//                     $originalItem = $originalItems->get($productId);
//                     $originalQuantity = $originalItem ? $originalItem->quantity : 0;
//                     $newQuantity = $item->quantity;

//                     $quantityDiff = $newQuantity - $originalQuantity;

//                     if ($quantityDiff != 0) {
//                         if (!$invoice->store || $invoice->store->trashed()) {
//                             throw new \Exception("انبار مرتبط با فاکتور فروش {$invoice->number} وجود ندارد یا حذف شده است.");
//                         }

//                         $currentStock = $invoice->store->getStock($item->product_id);

//                         if ($quantityDiff > 0) {
//                             $quantityToRemove = $quantityDiff;
//                             if ($currentStock < $quantityToRemove) {
//                                 throw new \Exception("موجودی کافی برای محصول {$item->product->name} در انبار وجود ندارد.");
//                             }
//                             $transactionType = 'exit';
//                             $quantityToApply = $quantityToRemove;
//                         } elseif ($quantityDiff < 0) {
//                             $quantityToAdd = abs($quantityDiff);
//                             $transactionType = 'entry';
//                             $quantityToApply = $quantityToAdd;
//                         }

//                         $uniqueReference = 'EDIT-SALE-INV-' . $invoice->number . '-' . $transactionType . '-' . now()->timestamp;

//                         $newTransaction = StoreTransaction::create([
//                             'store_id' => $invoice->store_id,
//                             'type' => $transactionType,
//                             'date' => now(),
//                             'reference' => $uniqueReference,
//                         ]);

//                         StoreTransactionItem::create([
//                             'store_transaction_id' => $newTransaction->id,
//                             'product_id' => $item->product_id,
//                             'quantity' => $quantityToApply,
//                         ]);
//                     }

//                     StoreTransactionItem::updateOrCreate(
//                         [
//                             'store_transaction_id' => $storeTransaction->id,
//                             'product_id' => $item->product_id,
//                         ],
//                         [
//                             'quantity' => $newQuantity,
//                         ]
//                     );
//                 }

//                 $originalProductIds = $originalItems->pluck('product_id')->toArray();
//                 $newProductIds = $newItems->pluck('product_id')->toArray();
//                 $deletedProductIds = array_diff($originalProductIds, $newProductIds);

//                 foreach ($deletedProductIds as $productId) {
//                     $originalItem = $originalItems->get($productId);
//                     $quantityToAdd = $originalItem->quantity;

//                     if (!$invoice->store || $invoice->store->trashed()) {
//                         throw new \Exception("انبار مرتبط با فاکتور فروش {$invoice->number} وجود ندارد یا حذف شده است.");
//                     }

//                     $uniqueReference = 'EDIT-SALE-INV-' . $invoice->number . '-DELETE-' . now()->timestamp;

//                     $entryTransaction = StoreTransaction::create([
//                         'store_id' => $invoice->store_id,
//                         'type' => 'entry',
//                         'date' => now(),
//                         'reference' => $uniqueReference,
//                     ]);

//                     StoreTransactionItem::create([
//                         'store_transaction_id' => $entryTransaction->id,
//                         'product_id' => $productId,
//                         'quantity' => $quantityToAdd,
//                     ]);

//                     StoreTransactionItem::where('store_transaction_id', $storeTransaction->id)
//                         ->where('product_id', $productId)
//                         ->delete();
//                 }

//                 Log::info("فاکتور فروش شماره {$invoice->number} با موفقیت ویرایش شد.", [
//                     'invoice_id' => $invoice->id,
//                     'store_id' => $invoice->store_id,
//                     'items' => $invoice->items->toArray(),
//                 ]);
//             }
//         }
//     });

//     // حذف فاکتور (خرید و برگشت خرید)
//     static::deleting(function ($invoice) {
//         if ($invoice->type === 'purchase') {
//             if (!$invoice->store || $invoice->store->trashed()) {
//                 Log::warning("فاکتور {$invoice->number} به انبار معتبر متصل نیست یا انبار حذف شده است.", [
//                     'invoice_id' => $invoice->id,
//                     'store_id' => $invoice->store_id,
//                 ]);
//                 $invoice->items()->delete();
//                 return;
//             }

//             $storeTransaction = StoreTransaction::where('reference', 'INV-' . $invoice->number)->first();
//             if ($storeTransaction) {
//                 $storeTransaction->update(['type' => 'exit']);
//             } else {
//                 $newTransaction = StoreTransaction::create([
//                     'store_id' => $invoice->store_id,
//                     'type' => 'exit',
//                     'date' => now(),
//                     'reference' => 'DEL-INV-' . $invoice->number . '-' . now()->timestamp,
//                 ]);

//                 foreach ($invoice->items as $item) {
//                     $currentStock = $invoice->store->getStock($item->product_id);
//                     if ($currentStock < $item->quantity) {
//                         Log::error("موجودی کافی برای محصول {$item->product->name} در انبار {$invoice->store->title} وجود ندارد.", [
//                             'invoice_id' => $invoice->id,
//                             'store_id' => $invoice->store_id,
//                             'product_id' => $item->product_id,
//                             'current_stock' => $currentStock,
//                             'required_quantity' => $item->quantity,
//                         ]);
//                         throw new \Exception("موجودی کافی برای محصول {$item->product->name} در انبار وجود ندارد.");
//                     }

//                     StoreTransactionItem::create([
//                         'store_transaction_id' => $newTransaction->id,
//                         'product_id' => $item->product_id,
//                         'quantity' => $item->quantity,
//                     ]);
//                 }
//             }

//             $invoice->items()->delete();

//             Log::info("فاکتور خرید شماره {$invoice->number} با موفقیت حذف شد.", [
//                 'invoice_id' => $invoice->id,
//                 'store_id' => $invoice->store_id,
//                 'items' => $invoice->items->toArray(),
//             ]);
//         } elseif ($invoice->type === 'purchase_return') {
//             if (!$invoice->store || $invoice->store->trashed()) {
//                 Log::warning("فاکتور برگشت {$invoice->number} به انبار معتبر متصل نیست یا انبار حذف شده است.", [
//                     'invoice_id' => $invoice->id,
//                     'store_id' => $invoice->store_id,
//                 ]);
//                 $invoice->items()->delete();
//                 return;
//             }

//             $storeTransaction = StoreTransaction::where('reference', 'RET-INV-' . $invoice->number)->first();
//             if ($storeTransaction) {
//                 $storeTransaction->update(['type' => 'entry']);
//             } else {
//                 $newTransaction = StoreTransaction::create([
//                     'store_id' => $invoice->store_id,
//                     'type' => 'entry',
//                     'date' => now(),
//                     'reference' => 'DEL-RET-INV-' . $invoice->number . '-' . now()->timestamp,
//                 ]);

//                 foreach ($invoice->items as $item) {
//                     StoreTransactionItem::create([
//                         'store_transaction_id' => $newTransaction->id,
//                         'product_id' => $item->product_id,
//                         'quantity' => $item->quantity,
//                     ]);
//                 }
//             }

//             $invoice->items()->delete();

//             Log::info("فاکتور برگشت خرید شماره {$invoice->number} با موفقیت حذف شد.", [
//                 'invoice_id' => $invoice->id,
//                 'store_id' => $invoice->store_id,
//                 'items' => $invoice->items->toArray(),
//             ]);
//         }
//     });
// }
protected static function booted()
{
    static::created(function ($invoice) {
        // منطق فعلی برای ایجاد فاکتورها (بدون تغییر)
        if ($invoice->type === 'purchase') {
            $transaction = StoreTransaction::create([
                'store_id' => $invoice->store_id,
                'type' => 'entry',
                'date' => $invoice->date,
                'reference' => 'INV-' . $invoice->number,
            ]);

            foreach ($invoice->items as $item) {
                StoreTransactionItem::create([
                    'store_transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ]);
            }
        } elseif ($invoice->type === 'purchase_return') {
            $transaction = StoreTransaction::create([
                'store_id' => $invoice->store_id,
                'type' => 'exit',
                'date' => $invoice->date,
                'reference' => 'RET-INV-' . $invoice->number,
            ]);

            foreach ($invoice->items as $item) {
                StoreTransactionItem::create([
                    'store_transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ]);
            }
        } elseif ($invoice->type === 'sale') {
            $transaction = StoreTransaction::create([
                'store_id' => $invoice->store_id,
                'type' => 'exit',
                'date' => $invoice->date,
                'reference' => 'SALE-INV-' . $invoice->number,
            ]);

            foreach ($invoice->items as $item) {
                $currentStock = $invoice->store->getStock($item->product_id);
                if ($currentStock < $item->quantity) {
                    throw new \Exception("موجودی کافی برای محصول {$item->product->name} در انبار وجود ندارد.");
                }

                StoreTransactionItem::create([
                    'store_transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ]);
            }

            Log::info("فاکتور فروش شماره {$invoice->number} با موفقیت ایجاد شد.", [
                'invoice_id' => $invoice->id,
                'store_id' => $invoice->store_id,
                'items' => $invoice->items->toArray(),
            ]);
        }
    });

    static::updated(function ($invoice) {
        if ($invoice->type === 'purchase') {
            $storeTransaction = StoreTransaction::where('reference', 'INV-' . $invoice->number)->first();
                        if ($storeTransaction) {
                            $originalItems = InvoiceItem::where('invoice_id', $invoice->id)
                                ->whereNull('deleted_at')
                                ->get()
                                ->keyBy('product_id');
            
                            $newItems = $invoice->items->keyBy('product_id');
            
                            foreach ($newItems as $productId => $item) {
                                $originalItem = $originalItems->get($productId);
                                $originalQuantity = $originalItem ? $originalItem->quantity : 0;
                                $newQuantity = $item->quantity;
            
                                $quantityDiff = $newQuantity - $originalQuantity;
            
                                if ($quantityDiff != 0) {
                                    if (!$invoice->store || $invoice->store->trashed()) {
                                        throw new \Exception("انبار مرتبط با فاکتور {$invoice->number} وجود ندارد یا حذف شده است.");
                                    }
            
                                    $currentStock = $invoice->store->getStock($item->product_id);
            
                                    if ($quantityDiff > 0) {
                                        $transactionType = 'entry';
                                        $quantityToApply = $quantityDiff;
                                    } elseif ($quantityDiff < 0) {
                                        $quantityToRemove = abs($quantityDiff);
                                        if ($currentStock < $quantityToRemove) {
                                            throw new \Exception("موجودی کافی برای محصول {$item->product->name} در انبار وجود ندارد.");
                                        }
                                        $transactionType = 'exit';
                                        $quantityToApply = $quantityToRemove;
                                    }
            
                                    $uniqueReference = 'EDIT-INV-' . $invoice->number . '-' . $transactionType . '-' . now()->timestamp;
            
                                    $newTransaction = StoreTransaction::create([
                                        'store_id' => $invoice->store_id,
                                        'type' => $transactionType,
                                        'date' => now(),
                                        'reference' => $uniqueReference,
                                    ]);
            
                                    StoreTransactionItem::create([
                                        'store_transaction_id' => $newTransaction->id,
                                        'product_id' => $item->product_id,
                                        'quantity' => $quantityToApply,
                                    ]);
                                }
            
                                StoreTransactionItem::updateOrCreate(
                                    [
                                        'store_transaction_id' => $storeTransaction->id,
                                        'product_id' => $item->product_id,
                                    ],
                                    [
                                        'quantity' => $newQuantity,
                                    ]
                                );
                            }
            
                            $originalProductIds = $originalItems->pluck('product_id')->toArray();
                            $newProductIds = $newItems->pluck('product_id')->toArray();
                            $deletedProductIds = array_diff($originalProductIds, $newProductIds);
            
                            foreach ($deletedProductIds as $productId) {
                                $originalItem = $originalItems->get($productId);
                                $quantityToRemove = $originalItem->quantity;
            
                                if (!$invoice->store || $invoice->store->trashed()) {
                                    throw new \Exception("انبار مرتبط با فاکتور {$invoice->number} وجود ندارد یا حذف شده است.");
                                }
            
                                $currentStock = $invoice->store->getStock($productId);
            
                                if ($currentStock < $quantityToRemove) {
                                    throw new \Exception("موجودی کافی برای محصول حذف‌شده در انبار وجود ندارد.");
                                }
            
                                $uniqueReference = 'EDIT-INV-' . $invoice->number . '-DELETE-' . now()->timestamp;
            
                                $exitTransaction = StoreTransaction::create([
                                    'store_id' => $invoice->store_id,
                                    'type' => 'exit',
                                    'date' => now(),
                                    'reference' => $uniqueReference,
                                ]);
            
                                StoreTransactionItem::create([
                                    'store_transaction_id' => $exitTransaction->id,
                                    'product_id' => $productId,
                                    'quantity' => $quantityToRemove,
                                ]);
            
                                StoreTransactionItem::where('store_transaction_id', $storeTransaction->id)
                                    ->where('product_id', $productId)
                                    ->delete();
                            }
            
                            Log::info("فاکتور خرید شماره {$invoice->number} با موفقیت ویرایش شد.", [
                                'invoice_id' => $invoice->id,
                                'store_id' => $invoice->store_id,
                                'items' => $invoice->items->toArray(),
                            ]);
                        }
        } elseif ($invoice->type === 'purchase_return') {
            $storeTransaction = StoreTransaction::where('reference', 'RET-INV-' . $invoice->number)->first();
                        if ($storeTransaction) {
                            $originalItems = InvoiceItem::where('invoice_id', $invoice->id)
                                ->whereNull('deleted_at')
                                ->get()
                                ->keyBy('product_id');
            
                            $newItems = $invoice->items->keyBy('product_id');
            
                            foreach ($newItems as $productId => $item) {
                                $originalItem = $originalItems->get($productId);
                                $originalQuantity = $originalItem ? $originalItem->quantity : 0;
                                $newQuantity = $item->quantity;
            
                                $quantityDiff = $newQuantity - $originalQuantity;
            
                                if ($quantityDiff != 0) {
                                    if (!$invoice->store || $invoice->store->trashed()) {
                                        throw new \Exception("انبار مرتبط با فاکتور برگشت {$invoice->number} وجود ندارد یا حذف شده است.");
                                    }
            
                                    $currentStock = $invoice->store->getStock($item->product_id);
            
                                    if ($quantityDiff > 0) {
                                        $quantityToRemove = $quantityDiff;
                                        if ($currentStock < $quantityToRemove) {
                                            throw new \Exception("موجودی کافی برای محصول {$item->product->name} در انبار وجود ندارد.");
                                        }
                                        $transactionType = 'exit';
                                        $quantityToApply = $quantityToRemove;
                                    } elseif ($quantityDiff < 0) {
                                        $quantityToAdd = abs($quantityDiff);
                                        $transactionType = 'entry';
                                        $quantityToApply = $quantityToAdd;
                                    }
            
                                    $uniqueReference = 'EDIT-RET-INV-' . $invoice->number . '-' . $transactionType . '-' . now()->timestamp;
            
                                    $newTransaction = StoreTransaction::create([
                                        'store_id' => $invoice->store_id,
                                        'type' => $transactionType,
                                        'date' => now(),
                                        'reference' => $uniqueReference,
                                    ]);
            
                                    StoreTransactionItem::create([
                                        'store_transaction_id' => $newTransaction->id,
                                        'product_id' => $item->product_id,
                                        'quantity' => $quantityToApply,
                                    ]);
                                }
            
                                StoreTransactionItem::updateOrCreate(
                                    [
                                        'store_transaction_id' => $storeTransaction->id,
                                        'product_id' => $item->product_id,
                                    ],
                                    [
                                        'quantity' => $newQuantity,
                                    ]
                                );
                            }
            
                            $originalProductIds = $originalItems->pluck('product_id')->toArray();
                            $newProductIds = $newItems->pluck('product_id')->toArray();
                            $deletedProductIds = array_diff($originalProductIds, $newProductIds);
            
                            foreach ($deletedProductIds as $productId) {
                                $originalItem = $originalItems->get($productId);
                                $quantityToAdd = $originalItem->quantity;
            
                                if (!$invoice->store || $invoice->store->trashed()) {
                                    throw new \Exception("انبار مرتبط با فاکتور برگشت {$invoice->number} وجود ندارد یا حذف شده است.");
                                }
            
                                $uniqueReference = 'EDIT-RET-INV-' . $invoice->number . '-DELETE-' . now()->timestamp;
            
                                $entryTransaction = StoreTransaction::create([
                                    'store_id' => $invoice->store_id,
                                    'type' => 'entry',
                                    'date' => now(),
                                    'reference' => $uniqueReference,
                                ]);
            
                                StoreTransactionItem::create([
                                    'store_transaction_id' => $entryTransaction->id,
                                    'product_id' => $productId,
                                    'quantity' => $quantityToAdd,
                                ]);
            
                                StoreTransactionItem::where('store_transaction_id', $storeTransaction->id)
                                    ->where('product_id' , $productId)
                                    ->delete();
                            }
            
                            Log::info("فاکتور برگشت خرید شماره {$invoice->number} با موفقیت ویرایش شد.", [
                                'invoice_id' => $invoice->id,
                                'store_id' => $invoice->store_id,
                                'items' => $invoice->items->toArray(),
                            ]);
                        }
        } elseif ($invoice->type === 'sale') {
            $storeTransaction = StoreTransaction::where('reference', 'SALE-INV-' . $invoice->number)->first();
        if ($storeTransaction) {
            // گرفتن آیتم‌های اصلی (قبل از ویرایش)
            $originalItems = InvoiceItem::where('invoice_id', $invoice->id)
                ->whereNull('deleted_at')
                ->get()
                ->keyBy('product_id');

            // گرفتن آیتم‌های جدید (بعد از ویرایش)
            $newItems = $invoice->items->keyBy('product_id');
            // dd($originalItems,  $newItems );
            // بررسی تغییرات در آیتم‌های موجود
            foreach ($newItems as $productId => $item) {
                $originalItem = $originalItems->get($productId);
                $originalQuantity = $originalItem ? $originalItem->quantity : 0;
                $newQuantity = $item->quantity;

                $quantityDiff = $newQuantity - $originalQuantity;
                if ($quantityDiff != 0) {
                    if (!$invoice->store || $invoice->store->trashed()) {
                        throw new \Exception("انبار مرتبط با فاکتور فروش {$invoice->number} وجود ندارد یا حذف شده است.");
                    }

                    $currentStock = $invoice->store->getStock($item->product_id);

                    if ($quantityDiff > 0) {
                        // افزایش تعداد: از انبار کم می‌کنیم
                        $quantityToRemove = $quantityDiff;
                        if ($currentStock < $quantityToRemove) {
                            throw new \Exception("موجودی کافی برای محصول {$item->product->name} در انبار وجود ندارد.");
                        }
                        $transactionType = 'exit';
                        $quantityToApply = $quantityToRemove;
                    } elseif ($quantityDiff < 0) {
                        // کاهش تعداد: به انبار برمی‌گردونیم
                        $quantityToAdd = abs($quantityDiff);
                        $transactionType = 'entry';
                        $quantityToApply = $quantityToAdd;
                    }

                    // ثبت تراکنش جدید برای مابه‌التفاوت
                    $uniqueReference = 'EDIT-SALE-INV-' . $invoice->number . '-' . $transactionType . '-' . now()->timestamp;

                    $newTransaction = StoreTransaction::create([
                        'store_id' => $invoice->store_id,
                        'type' => $transactionType,
                        'date' => now(),
                        'reference' => $uniqueReference,
                    ]);

                    StoreTransactionItem::create([
                        'store_transaction_id' => $newTransaction->id,
                        'product_id' => $item->product_id,
                        'quantity' => $quantityToApply,
                    ]);
                }

                // به‌روزرسانی تراکنش اصلی فقط برای همگام‌سازی (بدون تأثیر روی موجودی)
                StoreTransactionItem::updateOrCreate(
                    [
                        'store_transaction_id' => $storeTransaction->id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'quantity' => $newQuantity,
                    ]
                );
            }

            // بررسی آیتم‌های حذف‌شده
            $originalProductIds = $originalItems->pluck('product_id')->toArray();
            $newProductIds = $newItems->pluck('product_id')->toArray();
            $deletedProductIds = array_diff($originalProductIds, $newProductIds);

            foreach ($deletedProductIds as $productId) {
                $originalItem = $originalItems->get($productId);
                $quantityToAdd = $originalItem->quantity;

                if (!$invoice->store || $invoice->store->trashed()) {
                    throw new \Exception("انبار مرتبط با فاکتور فروش {$invoice->number} وجود ندارد یا حذف شده است.");
                }

                $uniqueReference = 'EDIT-SALE-INV-' . $invoice->number . '-DELETE-' . now()->timestamp;

                $entryTransaction = StoreTransaction::create([
                    'store_id' => $invoice->store_id,
                    'type' => 'entry',
                    'date' => now(),
                    'reference' => $uniqueReference,
                ]);

                StoreTransactionItem::create([
                    'store_transaction_id' => $entryTransaction->id,
                    'product_id' => $productId,
                    'quantity' => $quantityToAdd,
                ]);

                // حذف از تراکنش اصلی
                StoreTransactionItem::where('store_transaction_id', $storeTransaction->id)
                    ->where('product_id', $productId)
                    ->delete();
            }

            Log::info("فاکتور فروش شماره {$invoice->number} با موفقیت ویرایش شد.", [
                'invoice_id' => $invoice->id,
                'store_id' => $invoice->store_id,
                'items' => $invoice->items->toArray(),
            ]);
        }
           
        }
    });

    static::deleting(function ($invoice) {
        // منطق فعلی برای حذف (بدون تغییر)
        if ($invoice->type === 'purchase') {
            if (!$invoice->store || $invoice->store->trashed()) {
                                Log::warning("فاکتور {$invoice->number} به انبار معتبر متصل نیست یا انبار حذف شده است.", [
                                    'invoice_id' => $invoice->id,
                                    'store_id' => $invoice->store_id,
                                ]);
                                $invoice->items()->delete();
                                return;
                            }
                
                            $storeTransaction = StoreTransaction::where('reference', 'INV-' . $invoice->number)->first();
                            if ($storeTransaction) {
                                $storeTransaction->update(['type' => 'exit']);
                            } else {
                                $newTransaction = StoreTransaction::create([
                                    'store_id' => $invoice->store_id,
                                    'type' => 'exit',
                                    'date' => now(),
                                    'reference' => 'DEL-INV-' . $invoice->number . '-' . now()->timestamp,
                                ]);
                
                                foreach ($invoice->items as $item) {
                                    $currentStock = $invoice->store->getStock($item->product_id);
                                    if ($currentStock < $item->quantity) {
                                        Log::error("موجودی کافی برای محصول {$item->product->name} در انبار {$invoice->store->title} وجود ندارد.", [
                                            'invoice_id' => $invoice->id,
                                            'store_id' => $invoice->store_id,
                                            'product_id' => $item->product_id,
                                            'current_stock' => $currentStock,
                                            'required_quantity' => $item->quantity,
                                        ]);
                                        throw new \Exception("موجودی کافی برای محصول {$item->product->name} در انبار وجود ندارد.");
                                    }
                
                                    StoreTransactionItem::create([
                                        'store_transaction_id' => $newTransaction->id,
                                        'product_id' => $item->product_id,
                                        'quantity' => $item->quantity,
                                    ]);
                                }
                            }
                
                            $invoice->items()->delete();
                
                            Log::info("فاکتور خرید شماره {$invoice->number} با موفقیت حذف شد.", [
                                'invoice_id' => $invoice->id,
                                'store_id' => $invoice->store_id,
                                'items' => $invoice->items->toArray(),
                            ]);
        } elseif ($invoice->type === 'purchase_return') {
            $storeTransaction = StoreTransaction::where('reference', 'RET-INV-' . $invoice->number)->first();
            if ($storeTransaction) {
                $storeTransaction->update(['type' => 'entry']);
            } else {
                $newTransaction = StoreTransaction::create([
                    'store_id' => $invoice->store_id,
                    'type' => 'entry',
                    'date' => now(),
                    'reference' => 'DEL-RET-INV-' . $invoice->number,
                ]);

                foreach ($invoice->items as $item) {
                    StoreTransactionItem::create([
                        'store_transaction_id' => $newTransaction->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                    ]);
                }
            }
        } elseif ($invoice->type === 'sale') {
            $storeTransaction = StoreTransaction::where('reference', 'SALE-INV-' . $invoice->number)->first();
            if ($storeTransaction) {
                $storeTransaction->update(['type' => 'entry']);
            } else {
                $newTransaction = StoreTransaction::create([
                    'store_id' => $invoice->store_id,
                    'type' => 'entry',
                    'date' => now(),
                    'reference' => 'DEL-SALE-INV-' . $invoice->number,
                ]);

                foreach ($invoice->items as $item) {
                    StoreTransactionItem::create([
                        'store_transaction_id' => $newTransaction->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                    ]);
                }
            }

            Log::info("فاکتور فروش شماره {$invoice->number} با موفقیت حذف شد.", [
                'invoice_id' => $invoice->id,
                'store_id' => $invoice->store_id,
                'items' => $invoice->items->toArray(),
            ]);
        }

        $invoice->items()->delete();
    });
}
}
