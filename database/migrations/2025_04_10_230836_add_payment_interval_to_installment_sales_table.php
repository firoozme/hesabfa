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
        Schema::table('installment_sales', function (Blueprint $table) {
            $table->integer('payment_interval')->default(30); // فاصله پرداختی به روز
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installment_sales', function (Blueprint $table) {
            $table->dropColumn('payment_interval');
        });
    }
};
