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
        Schema::create('inventory_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_count_id')->constrained()->onDelete('cascade');
            $table->integer('verified_quantity')->unsigned()->nullable();
            $table->enum('status', ['pending', 'verified', 'corrected'])->default('pending');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_verifications');
    }
};
