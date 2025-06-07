<?php

namespace App\Models;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['name', 'price', 'duration', 'features', 'is_active', 'is_default'];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public static function getDefaultPlan()
    {
        return self::where('is_default', true)->first();
    }
}
