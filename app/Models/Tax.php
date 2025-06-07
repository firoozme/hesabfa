<?php

namespace App\Models;

use App\Models\Company;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use LogsActivity;
    use SoftDeletes;

    public function company(){
        return $this->belongsTo(Company::class, 'company_id');
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
    }
}
