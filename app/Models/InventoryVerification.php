<?php

namespace App\Models;

use App\Models\Company;
use App\Models\InventoryCount;
use Illuminate\Database\Eloquent\Model;

class InventoryVerification extends Model
{
    protected $fillable = [
        'inventory_count_id',
        'verified_quantity',
        'status',
        'company_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function inventoryCount()
    {
        return $this->belongsTo(InventoryCount::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
