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
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->string('accounting_auto')->default('auto');
            $table->string('accounting_code');
            $table->string('name');
            $table->text('description')->nullable(); // توضیحات
            $table->string('switch_number')->nullable(); // شماره سوییچ پرداخت
            $table->string('terminal_number')->nullable(); // شماره ترمینال پرداخت
            $table->string('merchant_number')->nullable(); // شماره پذیرنده فروشگاهی
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funds');
    }
};
