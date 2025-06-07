<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductType extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($type) {
            // اضافه کردن company_id از کاربر لاگین‌شده
            if (auth('company')->check()) {
                $type->company_id = auth('company')->user()->id;
            }
        });
    }
}
