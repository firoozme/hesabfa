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
        Schema::table('people', function (Blueprint $table) {
            $table->decimal('previous_debt', 15, 0)->default(0)->comment('مبلغ بدهکاری');
            $table->decimal('previous_credit', 15, 0)->default(0)->comment('مبلغ بستانکاری');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['previous_debt', 'previous_credit']);
        });
    }
};
