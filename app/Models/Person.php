<?php

namespace App\Models;

use App\Models\City;
use App\Models\Account;
use App\Models\Company;
use App\Models\PersonTax;
use App\Models\PersonType;
use App\Models\BankAccount;
use App\Traits\LogsActivity;
use App\Models\Person as Per;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use LogsActivity;
    use SoftDeletes;
    protected $guarded =[];


    protected function casts(): array
    {
        return [
            'phone1' => 'array',
            'type' => 'array',

        ];
    }
    public function price_list(){
        return $this->belongsTo(PriceList::class);
    }

    public function banks(){
        return $this->hasMany(BankAccount::class);
    }
    public function city(){
        return $this->belongsTo(City::class,'city_id');
    }

    public function getCreatedAtJalaliAttribute()
    {
        return verta($this->created_at)->format('Y/m/d');
    }
    public function getBirthDateJalaliAttribute()
    {
        return verta($this->birth_date)->format('Y/m/d');
    }
    public function getMarriageDateJalaliAttribute()
    {
        return verta($this->marriage_date)->format('Y/m/d');
    }
    public function types()
    {
        return $this->belongsToMany(PersonType::class, 'person_person_type', 'person_id', 'person_type_id');
    }
    public function tax_type(){
        return $this->belongsTo(PersonTax::class,'person_tax_id');
    }
    public function account()
    {
        return $this->hasOne(Account::class, 'code', 'accounting_code');
    }
    public function person()
    {
        return $this->belongsTo(Per::class, 'person_id');
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function setLastnameAttribute($value)
    {
        $this->attributes['lastname'] = $value;
        $this->attributes['fullname'] = $this->firstname . ' ' . $value;
    }

}
