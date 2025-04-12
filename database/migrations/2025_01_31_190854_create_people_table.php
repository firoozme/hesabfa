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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
             $table->string('accounting_code')->comment('کد حسابداری (اتوماتیک یا دستی) (پیشفرض اتوماتیک)');
             $table->string('company_name')->nullable()->comment('نام شرکت (اختیاری)');
             $table->string('firstname')->comment('نام (اختیاری)');
             $table->string('lastname')->comment('نام خانوادگی(اختیاری)');
            //  $table->virtualAs("CONCAT(firstname, ' ', lastname)",'fullname')->nullable();

             $table->date('birth_date')->nullable()->comment('تاریخ تولد');
             $table->date('marriage_date')->nullable()->comment('تاریخ ازدواج');

             // تب عمومی
             $table->decimal('financial_credit', 10, 0)->default(0)->comment('اعتبار مالی (پیشفرض صفر)');
             $table->foreignId('price_list_id')->constrained()->nullable()->comment('لیست قیمت (که از جدول price_lists گرفته)');
             $table->string('tax_type')->comment('نوع مالیات')->nullable();
             $table->string('national_id')->nullable()->comment('شناسه ملی (اختیاری)');
             $table->string('registration_number')->nullable()->comment('شماره ثبت(اختیاری)');
             $table->string('branch_code')->nullable()->comment('کد شعبه (اختیاری)');
             $table->text('notes')->nullable()->comment('توضیحات (اختیاری)');

             // تب آدرس
             $table->unsignedBigInteger('city_id')->nullable()->comment('شهر (از جدول cities فیلدهایی که فیلد parent آنها صفر نیست)');
             $table->string('postal_code')->nullable()->comment('کد پستی');
             $table->string('address')->nullable()->comment('آدرس');

        //      // تب تماس
             $table->json('phone1')->comment('تلفن');
             $table->string('mobile')->nullable()->comment('موبایل');
             $table->string('fax')->nullable()->comment('فکس');
             $table->string('email')->nullable()->comment('ایمیل');
             $table->string('website')->nullable()->comment('وبسایت');
             $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
