<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class StoreProduct extends Model
{
    use LogsActivity;
    protected $table = 'store_product';
}
