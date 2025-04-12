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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('accounting_auto')->default('auto');
            $table->string('number')->unique();
            $table->timestamp('date');
            $table->string('entity'); // تأمین‌کننده یا مشتری
            $table->enum('type',['purchase','sale','purchase_return','sale_return'])->default('purchase');
            $table->foreignId('company_id')->constrained();
            $table->string('title')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
