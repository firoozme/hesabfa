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
        Schema::create('income_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('income_id')->constrained()->onDelete('cascade');
            $table->morphs('receivable'); // برای حساب‌های بانکی، تنخواه، صندوق، چک
            $table->decimal('amount', 15, 2); // مبلغ دریافتی
            $table->date('date'); // تاریخ دریافت
            $table->string('reference')->nullable(); // شماره مرجع (مثلاً برای چک)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_receipts');
    }
};
