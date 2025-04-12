<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('checks', function (Blueprint $table) {
            // اضافه کردن ستون company_id
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');

            // اضافه کردن ستون‌های پلی‌مورفیک
            $table->morphs('checkable'); // این خط دو ستون checkable_id و checkable_type رو اضافه می‌کنه
        });
    }

    public function down()
    {
        Schema::table('checks', function (Blueprint $table) {
            // حذف کلید خارجی و ستون‌ها در صورت rollback
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            $table->dropMorphs('checkable');
        });
    }
};
