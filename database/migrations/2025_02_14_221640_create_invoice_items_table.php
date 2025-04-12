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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
        $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_id')->constrained();
        $table->string('description')->nullable();
        $table->foreignId('product_category_id')->constrained();
        $table->string('unit');
        $table->boolean('is_active')->default(true);
        $table->integer('quantity')->default(0);
        $table->decimal('unit_price', 15, 0)->default(0);
        $table->decimal('sum_price', 15, 0)->default(0);
        $table->decimal('discount', 15, 0)->default(0);
        $table->decimal('discount_price', 15, 0)->default(0);
        $table->decimal('tax', 15, 0)->default(0);
        $table->decimal('tax_price', 15, 0)->default(0);
        $table->decimal('total_price', 15, 0)->default(0);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
