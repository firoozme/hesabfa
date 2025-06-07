<?php
namespace App\Models;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'type'       => 'string',
        'is_active'  => 'boolean',
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'discount_product_category');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // public function isActive()
    // {
    //     $now = now();
    //     $isActive = $this->is_active &&
    //                 (!$this->start_date || $this->start_date <= $now) &&
    //                 (!$this->end_date || $this->end_date >= $now);

    //     if ($this->recurrence_rule === 'weekly_wednesday') {
    //         $isActive = $isActive && $now->isWednesday();
    //     }
    //     return $isActive;
    // }
    public function isActive()
    {
        $now      = now()->setTimezone('Asia/Dubai'); // یا timezone سیستم شما (+04:00)
        $isActive = $this->is_active &&
            (! $this->start_date || $this->start_date <= $now) &&
            (! $this->end_date || $this->end_date >= $now);

        if ($this->recurrence_rule) {
            $isActive = $isActive && match ($this->recurrence_rule) {
                'weekly_monday' => $now->isMonday(),
                'weekly_tuesday' => $now->isTuesday(),
                'weekly_wednesday' => $now->isWednesday(),
                'weekly_thursday' => $now->isThursday(),
                'weekly_friday' => $now->isFriday(),
                'weekly_saturday' => $now->isSaturday(),
                'weekly_sunday' => $now->isSunday(),
                default => true, // اگر recurrence_rule ناشناخته باشد، شرط را true فرض می‌کنیم
            };
        }

        return $isActive;
    }

    public function getStartDateJalaliAttribute()
    {
        return verta($this->start_date);
    }
    public function getEndDateJalaliAttribute()
    {
        return verta($this->end_date);
    }
}
