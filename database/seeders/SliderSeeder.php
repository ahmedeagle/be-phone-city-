<?php

namespace Database\Seeders;

use App\Models\Slider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sliders = [
            [
                'title_en' => 'Welcome to City Phone',
                'title_ar' => 'مرحباً بك في سيتي فون',
                'description_en' => 'Discover the latest smartphones and electronics at unbeatable prices.',
                'description_ar' => 'اكتشف أحدث الهواتف الذكية والإلكترونيات بأسعار لا تقبل المنافسة.',
                'image' => 'assets/images/sliders/slider-1.jpg',
            ],
            [
                'title_en' => 'Premium Quality Products',
                'title_ar' => 'منتجات عالية الجودة',
                'description_en' => 'Shop from our wide selection of premium quality products with warranty.',
                'description_ar' => 'تسوق من مجموعتنا الواسعة من المنتجات عالية الجودة مع الضمان.',
                'image' => 'assets/images/sliders/slider-2.jpg',
            ],
            [
                'title_en' => 'Special Offers',
                'title_ar' => 'عروض خاصة',
                'description_en' => 'Don\'t miss out on our exclusive deals and special discounts.',
                'description_ar' => 'لا تفوت عروضنا الحصرية والخصومات الخاصة.',
                'image' => 'assets/images/sliders/slider-3.jpg',
            ],
        ];

        foreach ($sliders as $slider) {
            Slider::create($slider);
        }
    }
}
