<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class CompanyOtp extends Model
{
    use LogsActivity;
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
