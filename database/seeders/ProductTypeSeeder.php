<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function runWithCompanyId(int $companyId): void
    {
        $product_types = [
            ['title' => 'کالا'],
            ['title' => 'خدمات'],
           
        ];

        foreach ($product_types as &$product_type) {
            $product_type['company_id'] = $companyId;
        }

        DB::table('product_types')->insert($product_types);
    }
}
