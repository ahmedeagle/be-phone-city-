<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            [
                'name_en' => 'Riyadh',
                'name_ar' => 'الرياض',
                'shipping_fee' => 0.00,
                'order' => 1,
                'status' => true,
            ],
            [
                'name_en' => 'Jeddah',
                'name_ar' => 'جدة',
                'shipping_fee' => 15.00,
                'order' => 2,
                'status' => true,
            ],
            [
                'name_en' => 'Mecca',
                'name_ar' => 'مكة المكرمة',
                'shipping_fee' => 20.00,
                'order' => 3,
                'status' => true,
            ],
            [
                'name_en' => 'Medina',
                'name_ar' => 'المدينة المنورة',
                'shipping_fee' => 20.00,
                'order' => 4,
                'status' => true,
            ],
            [
                'name_en' => 'Dammam',
                'name_ar' => 'الدمام',
                'shipping_fee' => 15.00,
                'order' => 5,
                'status' => true,
            ],
            [
                'name_en' => 'Khobar',
                'name_ar' => 'الخبر',
                'shipping_fee' => 15.00,
                'order' => 6,
                'status' => true,
            ],
            [
                'name_en' => 'Dhahran',
                'name_ar' => 'الظهران',
                'shipping_fee' => 15.00,
                'order' => 7,
                'status' => true,
            ],
            [
                'name_en' => 'Taif',
                'name_ar' => 'الطائف',
                'shipping_fee' => 25.00,
                'order' => 8,
                'status' => true,
            ],
            [
                'name_en' => 'Abha',
                'name_ar' => 'أبها',
                'shipping_fee' => 30.00,
                'order' => 9,
                'status' => true,
            ],
            [
                'name_en' => 'Tabuk',
                'name_ar' => 'تبوك',
                'shipping_fee' => 35.00,
                'order' => 10,
                'status' => true,
            ],
            [
                'name_en' => 'Buraidah',
                'name_ar' => 'بريدة',
                'shipping_fee' => 20.00,
                'order' => 11,
                'status' => true,
            ],
            [
                'name_en' => 'Khamis Mushait',
                'name_ar' => 'خميس مشيط',
                'shipping_fee' => 30.00,
                'order' => 12,
                'status' => true,
            ],
            [
                'name_en' => 'Hail',
                'name_ar' => 'حائل',
                'shipping_fee' => 30.00,
                'order' => 13,
                'status' => true,
            ],
            [
                'name_en' => 'Najran',
                'name_ar' => 'نجران',
                'shipping_fee' => 35.00,
                'order' => 14,
                'status' => true,
            ],
            [
                'name_en' => 'Jazan',
                'name_ar' => 'جازان',
                'shipping_fee' => 35.00,
                'order' => 15,
                'status' => true,
            ],
            [
                'name_en' => 'Al-Kharj',
                'name_ar' => 'الخرج',
                'shipping_fee' => 15.00,
                'order' => 16,
                'status' => true,
            ],
            [
                'name_en' => 'Hofuf',
                'name_ar' => 'الهفوف',
                'shipping_fee' => 20.00,
                'order' => 17,
                'status' => true,
            ],
            [
                'name_en' => 'Al-Mubarraz',
                'name_ar' => 'المبرز',
                'shipping_fee' => 20.00,
                'order' => 18,
                'status' => true,
            ],
            [
                'name_en' => 'Sakaka',
                'name_ar' => 'سكاكا',
                'shipping_fee' => 40.00,
                'order' => 19,
                'status' => true,
            ],
            [
                'name_en' => 'Jubail',
                'name_ar' => 'الجبيل',
                'shipping_fee' => 20.00,
                'order' => 20,
                'status' => true,
            ],
            [
                'name_en' => 'Yanbu',
                'name_ar' => 'ينبع',
                'shipping_fee' => 25.00,
                'order' => 21,
                'status' => true,
            ],
            [
                'name_en' => 'Al-Khafji',
                'name_ar' => 'الخفجي',
                'shipping_fee' => 30.00,
                'order' => 22,
                'status' => true,
            ],
            [
                'name_en' => 'Arar',
                'name_ar' => 'عرعر',
                'shipping_fee' => 40.00,
                'order' => 23,
                'status' => true,
            ],
            [
                'name_en' => 'Qatif',
                'name_ar' => 'القطيف',
                'shipping_fee' => 15.00,
                'order' => 24,
                'status' => true,
            ],
            [
                'name_en' => 'Unaizah',
                'name_ar' => 'عنيزة',
                'shipping_fee' => 20.00,
                'order' => 25,
                'status' => true,
            ],
        ];

        foreach ($cities as $cityData) {
            City::create($cityData);
        }

        $this->command->info('Successfully seeded ' . count($cities) . ' Saudi Arabia cities.');
    }
}
