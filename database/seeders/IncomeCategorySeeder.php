<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class IncomeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // DB::table('income_categories')->truncate(); // حذف داده‌های قبلی

        // دسته‌بندی‌های اصلی
        $operational = DB::table('income_categories')->insertGetId([
            'title' => 'درآمد های عملیاتی',
            'description' => null,
            'parent_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $nonOperational = DB::table('income_categories')->insertGetId([
            'title' => 'درآمد های غیر عملیاتی',
            'description' => null,
            'parent_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // زیرمجموعه‌های درآمد عملیاتی
        DB::table('income_categories')->insert([
            [
                'title' => 'درآمد اضافه کالا',
                'description' => null,
                'parent_id' => $operational,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'درآمد حمل کالا',
                'description' => null,
                'parent_id' => $operational,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // زیرمجموعه‌های درآمد غیر عملیاتی
        DB::table('income_categories')->insert([
            [
                'title' => 'درآمد حاصل از سرمایه گذاری',
                'description' => null,
                'parent_id' => $nonOperational,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'درآمد سود سپرده ها',
                'description' => null,
                'parent_id' => $nonOperational,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'سایر درآمد ها',
                'description' => null,
                'parent_id' => $nonOperational,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'درآمد تسعیر ارز',
                'description' => null,
                'parent_id' => $nonOperational,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'سود فروش اقساطی',
                'description' => null,
                'parent_id' => $nonOperational,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
