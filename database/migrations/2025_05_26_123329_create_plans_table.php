<?php

use App\Models\Plan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('price'); // 0 برای پلن رایگان
            $table->integer('duration'); // تعداد روزها
            $table->text('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // برای پلن پیش‌فرض رایگان
            $table->timestamps();
        });
        // ایجاد پلن پیش‌فرض رایگان
        Plan::create([
            'name' => 'رایگان',
            'price' => 0,
            'duration' => 7, // قابل تنظیم توسط ادمین
            'features' => json_encode(['ویژگی‌ها' => 'تست رایگان']),
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
