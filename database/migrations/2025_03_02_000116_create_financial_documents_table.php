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
        Schema::create('financial_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique(); // شماره سند
            $table->date('date'); // تاریخ سند
            $table->text('description')->nullable(); // توضیحات سند
            $table->enum('status', ['draft', 'approved', 'posted'])->default('draft'); // وضعیت سند
            $table->enum('type', ['regular', 'opening', 'closing'])->default('regular');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_documents');
    }
};
