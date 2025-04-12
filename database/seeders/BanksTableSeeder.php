<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BanksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            ['name' => 'بانک ملی ایران', 'logo' => 'img/banks/melli.png'],
            ['name' => 'بانک سپه', 'logo' => 'img/banks/sepeh.png'],
            ['name' => 'بانک مسکن', 'logo' => 'img/banks/maskan.png'],
            ['name' => 'بانک کشاورزی', 'logo' => 'img/banks/keshavarzi.png'],
            ['name' => 'بانک صنعت و معدن', 'logo' => 'img/banks/sanatmadan.png'],
            ['name' => 'بانک توسعه صادرات ایران', 'logo' => 'img/banks/toseesaderat.png'],
            ['name' => 'بانک توسعه تعاون', 'logo' => 'img/banks/toseetavon.png'],
            ['name' => 'بانک اقتصاد نوین', 'logo' => 'img/banks/eghtesadnovin.png'],
            ['name' => 'بانک پارسیان', 'logo' => 'img/banks/parsian.png'],
            ['name' => 'بانک کارآفرین', 'logo' => 'img/banks/karafarin.png'],
            ['name' => 'بانک سامان', 'logo' => 'img/banks/saman.png'],
            ['name' => 'بانک سینا', 'logo' => 'img/banks/sina.png'],
            ['name' => 'بانک خاورمیانه', 'logo' => 'img/banks/khavarmiane.png'],
            ['name' => 'بانک شهر', 'logo' => 'img/banks/shahr.png'],
            ['name' => 'بانک دی', 'logo' => 'img/banks/day.png'],
            ['name' => 'بانک صادرات ایران', 'logo' => 'img/banks/saderat.png'],
            ['name' => 'بانک ملت', 'logo' => 'img/banks/mellat.png'],
            ['name' => 'بانک تجارت', 'logo' => 'img/banks/tejarat.png'],
            ['name' => 'بانک رفاه کارگران', 'logo' => 'img/banks/refah.png'],
            ['name' => 'بانک قرض‌الحسنه مهر ایران', 'logo' => 'img/banks/mehreibank.png'],
            ['name' => 'بانک قرض‌الحسنه رسالت', 'logo' => 'img/banks/gharzolhasane.png'],
            ['name' => 'پست بانک ایران', 'logo' => 'img/banks/postbank.png'],
        ];

        DB::table('banks')->insert($banks);
    }
}
