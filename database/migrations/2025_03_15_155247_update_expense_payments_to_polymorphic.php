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
        Schema::table('expense_payments', function (Blueprint $table) {
            // حذف ستون payment_method_id
            // $table->dropForeign(['payment_method_id']);
            // $table->dropColumn('payment_method_id');

            // // اضافه کردن ستون‌های polymorphic
            // $table->morphs('paymentable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('polymorphic', function (Blueprint $table) {
        //     //
        // });
    }
};
