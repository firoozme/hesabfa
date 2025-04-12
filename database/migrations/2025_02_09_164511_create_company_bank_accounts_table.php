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
        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('accounting_code')->comment('Automatic or manual mode');
            $table->string('name');
            $table->string('account_number')->nullable();
            $table->string('card_number');
            $table->string('iban')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('pos_number')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('bank_id')->constrained();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_bank_accounts');
    }
};
