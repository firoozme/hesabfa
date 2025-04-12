<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountingCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AccountingCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lists = [
            ['id' => 92, 'title' => 'هزینه های پرسنلی', 'description' => '', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 93, 'title' => 'هزینه حقوق و دستمزد', 'description' => '', 'parent_id' => 92, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 107, 'title' => 'سایر هزینه های کارکنان', 'description' => '', 'parent_id' => 92, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 108, 'title' => 'سفر و ماموریت', 'description' => '', 'parent_id' => 107, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 109, 'title' => 'ایاب و ذهاب', 'description' => '', 'parent_id' => 107, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 110, 'title' => 'سایر هزینه های کارکنان', 'description' => '', 'parent_id' => 107, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 111, 'title' => 'هزینه های عملیاتی', 'description' => '', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 114, 'title' => 'هزینه حمل کالا', 'description' => '', 'parent_id' => 111, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 115, 'title' => 'تعمیر و نگهداری اموال و اثاثیه', 'description' => '', 'parent_id' => 111, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 116, 'title' => 'هزینه اجاره محل', 'description' => '', 'parent_id' => 111, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 117, 'title' => 'هزینه های عمومی', 'description' => '', 'parent_id' => 111, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 118, 'title' => 'هزینه آب و برق و گاز و تلفن', 'description' => '', 'parent_id' => 117, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 119, 'title' => 'هزینه پذیرایی و آبدارخانه', 'description' => '', 'parent_id' => 117, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 120, 'title' => 'هزینه ملزومات مصرفی', 'description' => '', 'parent_id' => 111, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 121, 'title' => 'هزینه کسری و ضایعات کالا', 'description' => '', 'parent_id' => 111, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 122, 'title' => 'بیمه دارایی های ثابت', 'description' => '', 'parent_id' => 111, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 123, 'title' => 'هزینه های استهلاک', 'description' => '', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 124, 'title' => 'هزینه استهلاک ساختمان', 'description' => '', 'parent_id' => 123, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 125, 'title' => 'هزینه استهلاک وسائط نقلیه', 'description' => '', 'parent_id' => 123, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 126, 'title' => 'هزینه استهلاک اثاثیه', 'description' => '', 'parent_id' => 123, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 127, 'title' => 'هزینه های بازاریابی و توزیع و فروش', 'description' => '', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 128, 'title' => 'هزینه آگهی و تبلیغات', 'description' => '', 'parent_id' => 127, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 129, 'title' => 'هزینه بازاریابی و پورسانت', 'description' => '', 'parent_id' => 127, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 130, 'title' => 'سایر هزینه های توزیع و فروش', 'description' => '', 'parent_id' => 127, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 131, 'title' => 'هزینه های غیرعملیاتی', 'description' => '', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 132, 'title' => 'هزینه های بانکی', 'description' => '', 'parent_id' => 131, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 133, 'title' => 'سود و کارمزد وامها', 'description' => '', 'parent_id' => 132, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 134, 'title' => 'کارمزد خدمات بانکی', 'description' => '', 'parent_id' => 132, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 135, 'title' => 'جرائم دیرکرد بانکی', 'description' => '', 'parent_id' => 132, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 136, 'title' => 'هزینه تسعیر ارز', 'description' => '', 'parent_id' => 131, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 137, 'title' => 'هزینه مطالبات سوخت شده', 'description' => '', 'parent_id' => 131, 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($lists as $list) {
            AccountingCategory::updateOrCreate(['id' => $list['id']], $list);
        }
    }
}
