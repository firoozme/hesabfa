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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_sale_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 0); // مبلغ قسط
            $table->date('due_date'); // تاریخ سررسید
            $table->string('status')->default('pending'); // pending, paid
            $table->foreignId('income_id')->nullable()->constrained()->onDelete('set null'); // درآمد مرتبط
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
