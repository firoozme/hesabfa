<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyIds = Company::all()->pluck('id')->toArray();

        // Seeder 1: Creating a sample product for "Goods"
        Product::create([
            'name' => 'محصول 1',
            'barcode' => '111,222,333',
            'image' => 'product1.jpg',
            'selling_price' => 1000000,
            'purchase_price' => 800000,
            'inventory' => 50,
            'minimum_order' => 1,
            'lead_time' => 3,
            'reorder_point' => 10,
            'sales_tax' => 100,
            'purchase_tax' => 100,
            'type' => 'Goods',
            'product_unit_id' => 1,  // Assuming 1 is a valid product unit ID
            'tax_id' => 1,  // Assuming 1 is a valid tax ID
            'company_id' => $companyIds[array_rand($companyIds)],  // Assuming 1 is a valid company ID
        ]);

        // Seeder 2: Creating another product for "Services"
        Product::create([
            'name' => 'محصول 2',
            'barcode' => '333,444',
            'image' => 'product2.jpg',
            'selling_price' => 5000000,
            'purchase_price' => 4500000,
            'inventory' => 10,
            'minimum_order' => 1,
            'lead_time' => 7,
            'reorder_point' => 3,
            'sales_tax' => 50,
            'purchase_tax' => 50,
            'type' => 'Services',
            'product_unit_id' => 2,  // Assuming 2 is a valid product unit ID
            'tax_id' => 2,  // Assuming 2 is a valid tax ID
            'company_id' => $companyIds[array_rand($companyIds)],  // Assuming 1 is a valid company ID
        ]);

        // Seeder 3: Creating a third product with different properties
        Product::create([
            'name' => 'محصول 3',
            'barcode' => '555,666',
            'image' => 'product3.jpg',
            'selling_price' => 15000000,
            'purchase_price' => 12000000,
            'inventory' => 30,
            'minimum_order' => 5,
            'lead_time' => 5,
            'reorder_point' => 15,
            'sales_tax' => 80,
            'purchase_tax' => 60,
            'type' => 'Goods',
            'product_unit_id' => 3,  // Assuming 3 is a valid product unit ID
            'tax_id' => 3,  // Assuming 3 is a valid tax ID
            'company_id' => $companyIds[array_rand($companyIds)],  // Assuming 2 is a valid company ID
        ]);
    }
}
