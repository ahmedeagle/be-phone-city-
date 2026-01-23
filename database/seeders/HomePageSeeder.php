<?php

namespace Database\Seeders;

use App\Models\HomePage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HomePageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HomePage::create([
            'offer_text_en' => 'Special Offer! Get up to 50% off on selected items. Limited time only!',
            'offer_text_ar' => 'عرض خاص! احصل على خصم يصل إلى 50٪ على المنتجات المحددة. لفترة محدودة فقط!',

            'app_title_en' => 'Download Our Mobile App',
            'app_title_ar' => 'حمّل تطبيقنا للهاتف المحمول',
            'app_description_en' => 'Get the best shopping experience on the go. Download our app now and enjoy exclusive deals and faster checkout.',
            'app_description_ar' => 'احصل على أفضل تجربة تسوق أثناء التنقل. حمّل تطبيقنا الآن واستمتع بعروض حصرية ودفع أسرع.',
            'app_main_image' => 'assets/images/app/main-image.jpg',

        ]);
    }
}
