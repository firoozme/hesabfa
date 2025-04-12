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
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('display_id')->default(true);
            $table->boolean('display_name')->default(true);
            $table->boolean('display_barcode')->default(false);
            $table->boolean('display_image')->default(false);
            $table->boolean('display_selling_price')->default(false);
            $table->boolean('display_purchase_price')->default(false);
            $table->boolean('display_inventory')->default(false);
            $table->boolean('display_minimum_order')->default(false);
            $table->boolean('display_lead_time')->default(false);
            $table->boolean('display_reorder_point')->default(false);
            $table->boolean('display_sales_tax')->default(false);
            $table->boolean('display_purchase_tax')->default(false);
            $table->boolean('display_type')->default(false);
            $table->boolean('display_unit')->default(false);
            $table->boolean('display_tax')->default(false);
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_lists');
    }
};
