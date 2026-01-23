<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class PagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'terms-and-conditions',
                'title_ar' => 'الشروط والأحكام',
                'title_en' => 'Terms & Conditions',
                'is_active' => true,
                'can_delete' => false,
            ],
            [
                'slug' => 'warranty-policy',
                'title_ar' => 'سياسة الضمان',
                'title_en' => 'Warranty Policy',
                'is_active' => true,
                'can_delete' => false,
            ],
            [
                'slug' => 'about-quwara',
                'title_ar' => 'المزيد عن كوارا',
                'title_en' => 'More About Qwara',
                'is_active' => true,
                'can_delete' => false,
            ],
            [
                'slug' => 'about-mowara',
                'title_ar' => 'المزيد عن موارا',
                'title_en' => 'More About Moara',
                'is_active' => true,
                'can_delete' => false,
            ],
            [
                'slug' => 'return-policy',
                'title_ar' => 'سياسة الإستبدال والإرجاع',
                'title_en' => 'Return & Exchange Policy',
                'is_active' => true,
                'can_delete' => false,
            ],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
