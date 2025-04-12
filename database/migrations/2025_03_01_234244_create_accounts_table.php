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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // کد حساب (مثلاً 101 برای نقد)
            $table->string('name'); // نام حساب (مثلاً "نقد و بانک")
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']); // نوع حساب
            $table->foreignId('company_id')->constrained(); // شرکت مرتبط
            $table->decimal('balance', 15, 2)->default(0); // مانده فعلی
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
