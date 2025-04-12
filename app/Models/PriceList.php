<?php

namespace App\Models;

use App\Models\Product;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'description',
        'is_active',
        'display_name',
        'display_barcode',
        'display_image',
        'display_selling_price',
        'display_purchase_price',
        'display_inventory',
        'display_minimum_order',
        'display_lead_time',
        'display_reorder_point',
        'display_sales_tax',
        'display_purchase_tax',
        'display_type',
        'display_unit',
        'display_tax',
        'company_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_name' => 'boolean',
        'display_barcode' => 'boolean',
        'display_image' => 'boolean',
        'display_selling_price' => 'boolean',
        'display_purchase_price' => 'boolean',
        'display_inventory' => 'boolean',
        'display_minimum_order' => 'boolean',
        'display_lead_time' => 'boolean',
        'display_reorder_point' => 'boolean',
        'display_sales_tax' => 'boolean',
        'display_purchase_tax' => 'boolean',
        'display_type' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {

            $model->company_id = auth('company')->user()->id;
        });
    }

    public function getStartDateJalaliAttribute(){
        return verta($this->start_date)->format('l j F Y');
    }
    public function getEndDateJalaliAttribute(){
        return verta($this->end_date)->format('l j F Y');
    }
    public function getCreatedAtJalaliAttribute(){
        return verta($this->created_at)->format('l j F Y');
    }


    public  function products(){
        return $this->belongsToMany(Product::class);
    }
}
