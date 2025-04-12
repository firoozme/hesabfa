<?php

namespace Database\Seeders;

use App\Models\PersonType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PersonTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'مشتری',
            'تامین کننده',
            'سهامدار',
            'کارمند',
            'بازاریاب',
        ];

        foreach ($types as $type) {
            PersonType::create([
                'title' => $type,
            ]);
        }
    }
}
