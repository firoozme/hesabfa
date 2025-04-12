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
            $table->foreignId('person_tax_id')->constrained();
            $table->foreignId('person_type_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropForeign('person_tax_id');
            $table->dropForeign('person_type_id');
            $table->dropColumn('person_tax_id');
            $table->dropColumn('person_type_id');
        });
    }
};
