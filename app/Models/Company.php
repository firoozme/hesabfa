<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Model;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Company extends Authenticatable implements  HasName
{
    use LogsActivity;
    protected $guarded =[];


    public function getFilamentName(): string
    {
        return $this->fullname ?? $this->mobile; // or $this->name, depending on how you want to identify users
    }

    public function getCreatedAtJalaliAttribute(){
        return verta($this->created_at);
    }
}
