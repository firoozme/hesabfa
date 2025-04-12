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
        Schema::create('opening_balances', function (Blueprint $table) {
            $table->id();
            $table->morphs('accountable'); // برای حساب‌های مختلف (Fund, BankAccount, ...)
            $table->decimal('amount', 15, 0); // مبلغ تراز
            $table->date('date'); // تاریخ تراز
            $table->foreignId('company_id')->constrained()->onDelete('cascade'); // شرکت مربوطه
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_balances');
    }
};
