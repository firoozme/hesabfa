<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Name of the product
            $table->string('name');

            // Product barcode (optional, can store multiple barcodes as a string or JSON)
            $table->string('barcode')->nullable();

            // Product image (optional, stores image file path or URL)
            $table->string('image')->nullable();

            // Selling price (stored with 2 decimal places)
            $table->decimal('selling_price', 10, 2);

            // Purchase price (stored with 2 decimal places)
            $table->decimal('purchase_price', 10, 2);

            // Inventory quantity (default value is 0)
            $table->integer('inventory')->default(0);

            // Minimum order quantity
            $table->integer('minimum_order')->default(1);

            // Lead time (time required to deliver the product)
            $table->integer('lead_time');
            $table->boolean('is_active')->default(true);

            // Reorder point (inventory level to trigger reorder)
            $table->integer('reorder_point');

            // Sales tax rate (stored with 2 decimal places)
            $table->decimal('sales_tax', 5, 2);

            // Purchase tax rate (stored with 2 decimal places)
            $table->decimal('purchase_tax', 5, 2);

            // Foreign key for product_unit_id, relates to product_units table
            $table->foreignId('product_unit_id')->constrained();

            // Foreign key for tax_id, relates to taxes table
            $table->foreignId('tax_id')->constrained();

            // Foreign key for company_id, relates to companies table
            $table->foreignId('company_id')->constrained();
            $table->foreignId('product_category_id')->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
