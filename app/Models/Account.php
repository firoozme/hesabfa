<?php

namespace App\Models;

use App\Models\Person;
use App\Models\Account;
use App\Traits\LogsActivity;
use App\Models\FinancialDocumentLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    public function getCreatedAtJalaliAttribute()
    {
        return verta($this->create_at)->format('Y/m/d');
    }
    public function persons()
    {
        return $this->hasMany(Person::class, 'account_id');
    }
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function documentLines()
    {
        return $this->hasMany(FinancialDocumentLine::class);
    }
}
