<?php

namespace App\Models;

use App\Models\Person;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonType extends Model
{
    use LogsActivity;
    use SoftDeletes;

    public function persons(){
        return $this->belongsToMany(Person::class);
    }
}
