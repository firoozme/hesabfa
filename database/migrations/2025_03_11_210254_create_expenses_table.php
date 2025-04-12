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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('accounting_auto')->default('auto');
            $table->string('accounting_code')->unique();
            $table->string('number')->unique(); // شماره
            $table->date('date'); // تاریخ
            $table->text('description')->nullable(); // شرح
            $table->string('status')->default('pending'); // وضعیت (pending/paid)
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
