<?php

namespace App\Models;

use App\Models\Product;
use App\Models\StoreProduct;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class StoreProduct extends Model
{
    use LogsActivity;
    protected $table = 'store_product';

    protected static function boot()
    {
        parent::boot();

        
    }
}
