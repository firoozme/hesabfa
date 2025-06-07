<?php

namespace App\Models;

use App\Models\Company;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Model
{
    use LogsActivity;
    use SoftDeletes;
    public function company(){
        return $this->belongsTo(Company::class, 'company_id');
    }
}
