<?php

namespace App\Models;

use App\Models\Product;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
{
    use LogsActivity;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'items' => 'array', // کست کردن فیلد items به آرایه
    ];
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function getCreatedAtJalaliAttribute()
    {
        return verta($this->created_at)->format('Y/m/d');
    }

    public function product(){
        return $this->belongsTo(Product::class)->withTrashed();

    }

    public function setTotalPriceAttribute($value)
    {
        // حذف کاماها از رشته ورودی
        $cleanValue = str_replace(',', '', $value);

        // تبدیل به عدد (ممکنه float باشه)، سپس رند کردن به عدد صحیح
        $this->attributes['total_price'] = round(floatval($cleanValue));
    }
    
}
