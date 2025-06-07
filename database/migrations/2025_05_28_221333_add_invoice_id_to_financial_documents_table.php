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
        Schema::table('financial_documents', function (Blueprint $table) {
            $table->foreignId('invoice_id')->constrained()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_documents', function (Blueprint $table) {
            $table->dropForeign('invoice_id');
            $table->dropColumn('invoice_id');
        });
    }
};
