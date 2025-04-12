<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            ['title' => 'دارو', 'value' => '0'],
            ['title' => 'دخانیات', 'value' => '0'],
            ['title' => 'موبایل', 'value' => '0'],
            ['title' => 'لوازم خانگی برقی', 'value' => '0'],
            ['title' => 'قطعات مصرفی و یدکی وسایل نقلیه', 'value' => '0'],
            ['title' => 'فرآورده‌ها و مشتقات نفتی و گازی و پتروشیمیایی', 'value' => '0'],
            ['title' => 'طلا اعم از شمش، مسکوکات و مصنوعات زینتی', 'value' => '0'],
            ['title' => 'منسوجات و پوشاک', 'value' => '0'],
            ['title' => 'اسباب بازی', 'value' => '0'],
            ['title' => 'دام زنده، گوشت سفید و قرمز', 'value' => '0'],
            ['title' => 'محصولات اساسی کشاورزی', 'value' => '0'],
            ['title' => 'سایر کالاها', 'value' => '0'],
        ];

        foreach ($taxes as $tax) {
            DB::table('taxes')->insert($tax);
        }
    }
}
