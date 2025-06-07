<?php

namespace App\Models;

use App\Models\Product;
use App\Traits\LogsActivity;
use App\Models\ProductCategory as PC;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PhpOffice\PhpSpreadsheet\Calculation\Category;

class ProductCategory extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = [
        'id'
    ];
    public function category()
    {
        return $this->belongsTo(PC::class, 'parent_id');
    }
    public function products(){
        return $this->hasMany(Product::class);
    }

    public static function boot()
    {
        parent::boot();
    
        static::creating(function ($category) {
            // اضافه کردن company_id از کاربر لاگین‌شده
            if (auth('company')->check()) {
                $category->company_id = auth('company')->user()->id;
            }
        });
    }
}
