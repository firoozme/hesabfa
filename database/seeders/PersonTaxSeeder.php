<?php

namespace Database\Seeders;

use App\Models\PersonTax;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PersonTaxSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'مصرف کننده نهایی',
            'عدم مشمول ثبت نام در نظام مالیاتی',
            'مشمول حقیقی ماده 81',
            'مشمول ثبت نام در نظام مالیاتی'
        ];

        foreach ($types as $type) {
            PersonTax::create([
                'title' => $type,
            ]);
        }
    }
}
