<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FiscalYear extends Model
{
    use LogsActivity;
    use SoftDeletes;
    protected $fillable =[
        'name'
    ];
}
