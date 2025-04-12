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
        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique(); // شماره صیاد
            $table->string('payer'); // پرداخت کننده
            $table->string('bank'); // بانک
            $table->string('branch'); // شعبه
            $table->decimal('amount', 15, 0); // مبلغ
            $table->date('date_received'); // تاریخ دریافت
            $table->date('due_date'); // تاریخ سررسید
            $table->enum('status', ['overdue', 'in_progress', 'received', 'returned', 'cashed']); // وضعیت
            $table->enum('type', ['receivable', 'payable']); // نوع چک
            $table->timestamps();
            $table->softDeletes(); // امکان حذف نرم
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checks');
    }
};
