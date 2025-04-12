<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $guarded = [];

    public function loggable()
    {
        return $this->morphTo();
    }

    public function getCreatedAtJalaliAttribute(){
        return verta($this->created_at);
    }
}
