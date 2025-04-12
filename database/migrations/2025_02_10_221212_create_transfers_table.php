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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('accounting_auto');
            $table->string('reference_number')->unique();
            $table->date('transfer_date');
            $table->decimal('amount', 15, 0);
            $table->text('description')->nullable();
            $table->foreignId('company_id')->constrained();

            // اطلاعات مبدا
            $table->morphs('source'); // source_id و source_type

            // اطلاعات مقصد
            $table->morphs('destination'); // destination_id و destination_type

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
