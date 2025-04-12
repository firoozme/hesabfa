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
        Schema::table('transfers', function (Blueprint $table) {
             // اضافه کردن فیلد جدید برای نوع تراکنش (payment یا transfer)
        $table->enum('transaction_type', ['transfer', 'payment'])->default('transfer');

        // اضافه کردن ارتباط با فاکتور
        $table->morphs('paymentable'); // paymentable_id و paymentable_type (برای اتصال به فاکتور)
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            //
        });
    }
};
