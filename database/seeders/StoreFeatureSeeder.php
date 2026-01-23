<?php

namespace Database\Seeders;

use App\Models\StoreFeature;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'name_en' => 'Fast Delivery',
                'name_ar' => 'توصيل سريع',
                'description_en' => 'We deliver your orders quickly and efficiently to your doorstep.',
                'description_ar' => 'نوصل طلباتك بسرعة وكفاءة إلى باب منزلك.',
                'image' => 'assets/images/features/fast-delivery.svg',
            ],
            [
                'name_en' => 'Secure Payment',
                'name_ar' => 'دفع آمن',
                'description_en' => 'Your payment information is protected with the latest security standards.',
                'description_ar' => 'معلومات الدفع الخاصة بك محمية بأحدث معايير الأمان.',
                'image' => 'assets/images/features/secure-payment.svg',
            ],
            [
                'name_en' => 'Quality Products',
                'name_ar' => 'منتجات عالية الجودة',
                'description_en' => 'We offer only the best quality products from trusted suppliers.',
                'description_ar' => 'نوفر فقط أفضل المنتجات عالية الجودة من موردين موثوقين.',
                'image' => 'assets/images/features/quality-products.svg',
            ],
            [
                'name_en' => '24/7 Support',
                'name_ar' => 'دعم على مدار الساعة',
                'description_en' => 'Our customer support team is available 24/7 to help you.',
                'description_ar' => 'فريق دعم العملاء لدينا متاح على مدار الساعة لمساعدتك.',
                'image' => 'assets/images/features/support.svg',
            ],
            [
                'name_en' => 'Easy Returns',
                'name_ar' => 'إرجاع سهل',
                'description_en' => 'Return products easily within 14 days if you are not satisfied.',
                'description_ar' => 'قم بإرجاع المنتجات بسهولة خلال 14 يومًا إذا لم تكن راضيًا.',
                'image' => 'assets/images/features/easy-returns.svg',
            ],
            [
                'name_en' => 'Best Prices',
                'name_ar' => 'أفضل الأسعار',
                'description_en' => 'We offer competitive prices and regular discounts on our products.',
                'description_ar' => 'نوفر أسعارًا تنافسية وخصومات منتظمة على منتجاتنا.',
                'image' => 'assets/images/features/best-prices.svg',
            ],
        ];

        foreach ($features as $feature) {
            StoreFeature::create($feature);
        }
    }
}
