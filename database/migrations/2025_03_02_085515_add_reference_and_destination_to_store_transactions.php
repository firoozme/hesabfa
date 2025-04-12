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
        Schema::table('store_transactions', function (Blueprint $table) {
            $table->string('reference')->nullable()->unique(); // شماره حواله
            $table->string('destination_type')->nullable(); // نوع مقصد (مثلاً Store, Customer)
            $table->unsignedBigInteger('destination_id')->nullable(); // شناسه مقصد
            $table->index(['destination_type', 'destination_id']); // ایندکس برای polymorphic
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_transactions', function (Blueprint $table) {
            $table->dropColumn(['reference', 'destination_type', 'destination_id']);
        });
    }
};
