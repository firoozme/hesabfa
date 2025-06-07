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
        Schema::create('cities', function (Blueprint $table) {
            $table->id(); // معادل bigint unsigned auto_increment
            $table->unsignedBigInteger('user_id')->default(1);
            $table->integer('parent')->default(0);
            $table->string('title', 100);
            $table->unsignedTinyInteger('sort')->default(1);
            $table->softDeletes(); // ستون deleted_at
            $table->timestamps(); // ستون‌های created_at و updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
