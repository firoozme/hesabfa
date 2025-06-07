<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Permission::query()->delete();
        $resources = [
            'bank' => 'بانک',
            'company' => 'شرکت',
            'fiscal_year' => 'سال مالی',
            'invoice' => 'فاکتور',
            'person' => 'اشخاص',
            'person_tax' => 'مالیات اشخاص',
            'person_type' => 'نوع اشخاص',
            'price_list' => 'لیست قیمت',
            'product' => 'محصول',
            'product_unit' => 'واحد محصول',
            'sale_invoice' => 'فاکتور فروش',
            'setting' => 'تنظیمات',
            'store' => 'انبار',
            'tax' => 'مالیات',
            'user' => 'کاربر',
            'role' => 'نقش',
            'plan' => 'پلن',

        ];

        $permissions = [
            'view_any' => 'مشاهده لیست',
            'view' => 'مشاهده',
            'create' => 'ایجاد',
            'update' => 'ویرایش',
            'delete' => 'حذف',
        ];

        
        $dashboard_items = [
            'dashboard' => 'داشبورد',
            'chart' => 'نمودار داشبورد',
            'widget' => ' ویجت داشبورد',
            'card' => 'کارت داشبورد',

        ];

        $dashboard_permissions = [
            'view' => 'مشاهده',
        ];

        foreach ($resources as $key => $resource) {
            foreach ($permissions as $permKey => $permLabel) {
                $name = "{$key}_{$permKey}";
                $name_fa = "{$permLabel} {$resource}";

                Permission::firstOrCreate(['name' => $name], ['name_fa' => $name_fa]);
            }
        }
        Permission::firstOrCreate(['name' => 'landing_update'], ['name_fa' => 'تنظیمات صفحه اصلی']);
        Permission::firstOrCreate(['name' => 'log_view'], ['name_fa' => 'مشاهده لاگ']);
        foreach ($dashboard_items as $key => $dashboard_item) {
            foreach ($dashboard_permissions as $permKey => $permLabel) {
                $name = "{$key}_{$permKey}";
                $name_fa = "{$permLabel} {$dashboard_item}";

                Permission::firstOrCreate(['name' => $name], ['name_fa' => $name_fa]);
            }
        }
    }

}
