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
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('amount');
            $table->string('payment_gateway');
            $table->string('authority')->nullable();
            $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->string('transaction_type')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->string('ref_id')->nullable();
            $table->string('card_pan')->nullable();
            $table->string('card_hash')->nullable();
            $table->integer('error_code')->nullable();
            $table->string('paymentable_type')->nullable();
            $table->unsignedBigInteger('paymentable_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
