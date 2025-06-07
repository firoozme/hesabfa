<?php

namespace App\Models;

use App\Models\Store;
use App\Models\Company;
use App\Models\Product;
use App\Models\InventoryVerification;
use Illuminate\Database\Eloquent\Model;

class InventoryCount extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function verification()
    {
        return $this->hasOne(InventoryVerification::class);
    }
}
