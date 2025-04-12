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
        Schema::create('person_types', function (Blueprint $table) {
            $table->id();

            // از طریق پنل مدیریت می توان موارددیکر را اضافه کرد
            $table->string('title'); // مشتری،تامین کننده،سهامدار،کارمند،بازاریاب یا ......
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('person_types');
    }
};
