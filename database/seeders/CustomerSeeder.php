<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::create([
            'firstname' =>'امید',
            'lastname' =>'مفید',
            'email' =>'mofidomid@gmail.com',
            'mobile' =>'09366168364',

        ]);
    }
}
