<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Product;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductUnit extends Model
{
    use LogsActivity;
    use SoftDeletes;
   
    public function company(){
        return $this->belongsTo(Company::class, 'company_id');
    }
    public function products()
    {
        return $this->hasMany(Product::class, 'product_unit_id');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($type) {
            // اضافه کردن company_id از کاربر لاگین‌شده
            if (auth('company')->check()) {
                $type->company_id = auth('company')->user()->id;
            }
        });
        // جلوگیری از حذف در صورت وجود محصول مرتبط
        static::deleting(function ($unit) {
            if ($unit->products()->exists()) {
                Notification::make()
                ->title('نمی‌توان واحد شمارش را حذف کرد زیرا محصولاتی به آن مرتبط هستند.')
                ->body('')
                ->danger()
                ->send();
                return false;
            }
        });
    }
}
