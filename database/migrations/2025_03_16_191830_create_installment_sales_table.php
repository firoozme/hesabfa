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
        Schema::create('installment_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->decimal('prepayment', 15, 0)->default(0); // پیش‌پرداخت
            $table->integer('installment_count'); // تعداد اقساط
            $table->decimal('annual_interest_rate', 5, 2); // نرخ بهره سالانه
            $table->date('start_date'); // تاریخ شروع اقساط
            $table->decimal('total_amount', 15, 0); // مبلغ کل با بهره
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installment_sales');
    }
};
