<?php
namespace App\Models;

use App\Models\Person;
use App\Models\Account;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use App\Traits\LogsActivity;
use App\Models\Invoice as INV;
use App\Models\StoreTransaction;
use App\Models\FinancialDocument;
use Illuminate\Support\Facades\DB;
use App\Models\StoreTransactionItem;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use LogsActivity;
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
    public function originalInvoice()
    {
        return $this->belongsTo(INV::class, 'parent_invoice_id');
    }

    public function returnInvoices()
    {
        return $this->hasMany(INV::class, 'parent_invoice_id');
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
        return $this->net_total_amount - $this->paid_amount;
        // return $this->total_amount - $this->paid_amount;
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
    public function getFullNameAttribute()
    {
        $name = $this->number;
        if ($this->title) {
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

        if ($this->type == 'sale') {
            if (! $this->is_installment) {
                return 0; // یا منطق پرداخت نقدی اگه دارید
            }

            $installmentSale  = $this->installmentSale;
            $prepayment       = $installmentSale->prepayment;
            $paidInstallments = $installmentSale->installments()->where('status', 'paid')->sum('amount');

            return $prepayment + $paidInstallments;

        } else {
            return $this->payments()->sum('amount');

        }
    }

    protected static function booted()
    {
        static::creating(function ($product) {
            // Manual initialization
            $product->company_id = auth('company')->user()->id;
        });

    }

  /**
     * محاسبه مبلغ خالص فاکتور خرید پس از کسر فاکتورهای برگشت خرید.
     */
    public function getNetTotalAmountAttribute(): float
    {
        if ($this->type !== 'purchase' && $this->type !== 'sale' ) {
            return $this->total_amount;
        }
     
    
        $totalReturns = $this->returnInvoices()
            ->with('items')
            ->get()
            ->sum(fn($invoice) => $invoice->items->sum('total_price'));
    
        return $this->total_amount - $totalReturns;
    }
    

    /**
     * محاسبه مقدار باقی‌مانده برای هر محصول پس از کسر مقادیر برگشتی.
     */
    public function getRemainingQuantityForProduct($productId): int
    {
        $originalQuantity = $this->items()->where('product_id', $productId)->sum('quantity');
        $returnedQuantity = $this->returnInvoices()
            ->with(['items' => fn($query) => $query->where('product_id', $productId)])
            ->get()
            ->sum(fn($return) => $return->items->sum('quantity'));
        return max(0, $originalQuantity - $returnedQuantity);
    }

    public function parentInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'parent_invoice_id');
    }
}
