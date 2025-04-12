<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::create([
            'firstname' => 'امید',
            'lastname' => 'مفید',
            'email' => 'mofidomid@gmail.com',
            'mobile' => '09366168364',
            'password' => Hash::make('Aa8511425290'),

        ]);
    }
}
