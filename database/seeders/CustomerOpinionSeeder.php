<?php

namespace Database\Seeders;

use App\Models\CustomerOpinion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerOpinionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $opinions = [
            [
                'name_en' => 'Ahmed Mohammed',
                'name_ar' => 'أحمد محمد',
                'description_en' => 'Excellent service and fast delivery! The products are of high quality and exactly as described. Highly recommended!',
                'description_ar' => 'خدمة ممتازة وتوصيل سريع! المنتجات عالية الجودة ومطابقة تماماً للوصف. أنصح بها بشدة!',
                'image' => 'assets/images/customers/customer-1.jpg',
                'rate' => 5,
            ],
            [
                'name_en' => 'Sarah Ali',
                'name_ar' => 'سارة علي',
                'description_en' => 'I am very satisfied with my purchase. The customer service team was very helpful and responsive. Great experience!',
                'description_ar' => 'أنا راضية جداً عن مشتريتي. فريق خدمة العملاء كان مفيداً جداً وسريع الاستجابة. تجربة رائعة!',
                'image' => 'assets/images/customers/customer-2.jpg',
                'rate' => 5,
            ],
            [
                'name_en' => 'Mohammed Hassan',
                'name_ar' => 'محمد حسن',
                'description_en' => 'Best online shopping experience I\'ve had. The website is easy to use and the products arrived in perfect condition.',
                'description_ar' => 'أفضل تجربة تسوق عبر الإنترنت قمت بها. الموقع سهل الاستخدام والمنتجات وصلت بحالة ممتازة.',
                'image' => 'assets/images/customers/customer-3.jpg',
                'rate' => 5,
            ],
            [
                'name_en' => 'Fatima Ibrahim',
                'name_ar' => 'فاطمة إبراهيم',
                'description_en' => 'Amazing quality and reasonable prices. I will definitely shop here again. Thank you for the great service!',
                'description_ar' => 'جودة مذهلة وأسعار معقولة. سأتسوق هنا مرة أخرى بالتأكيد. شكراً على الخدمة الرائعة!',
                'image' => 'assets/images/customers/customer-4.jpg',
                'rate' => 5,
            ],
            [
                'name_en' => 'Khalid Abdullah',
                'name_ar' => 'خالد عبدالله',
                'description_en' => 'Very professional service. The delivery was on time and the packaging was excellent. Highly satisfied!',
                'description_ar' => 'خدمة احترافية جداً. التوصيل كان في الوقت المحدد والتغليف كان ممتازاً. راضٍ جداً!',
                'image' => 'assets/images/customers/customer-5.jpg',
                'rate' => 5,
            ],
            [
                'name_en' => 'Noura Saleh',
                'name_ar' => 'نورة صالح',
                'description_en' => 'I love shopping from this store! Great variety of products and excellent customer support. Keep up the good work!',
                'description_ar' => 'أحب التسوق من هذا المتجر! تنوع رائع في المنتجات ودعم عملاء ممتاز. استمروا في العمل الجيد!',
                'image' => 'assets/images/customers/customer-6.jpg',
                'rate' => 5,
            ],
            [
                'name_en' => 'Omar Fahad',
                'name_ar' => 'عمر فهد',
                'description_en' => 'Outstanding service! The products exceeded my expectations. Fast shipping and secure payment. Very happy!',
                'description_ar' => 'خدمة استثنائية! المنتجات تجاوزت توقعاتي. شحن سريع ودفع آمن. سعيد جداً!',
                'image' => 'assets/images/customers/customer-7.jpg',
                'rate' => 5,
            ],
            [
                'name_en' => 'Layla Mansour',
                'name_ar' => 'ليلى منصور',
                'description_en' => 'Perfect shopping experience from start to finish. Quality products, great prices, and excellent service. Thank you!',
                'description_ar' => 'تجربة تسوق مثالية من البداية للنهاية. منتجات عالية الجودة، أسعار رائعة، وخدمة ممتازة. شكراً لكم!',
                'image' => 'assets/images/customers/customer-8.jpg',
                'rate' => 5,
            ],
        ];

        foreach ($opinions as $opinion) {
            CustomerOpinion::create($opinion);
        }
    }
}
