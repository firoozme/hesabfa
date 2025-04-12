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
    public function category(){
        return $this->belongsTo(PC::class,'parent_id');
    }
    public function products(){
        return $this->hasMany(Product::class);
    }
}
