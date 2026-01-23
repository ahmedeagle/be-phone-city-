<?php

namespace Database\Seeders;

use App\Models\Discount;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $discounts = [
            [
                'code' => 'FIRST10',
                'status' => true,
                'start' => now(),
                'end' => now()->addMonths(6),
                'description_en' => 'Get 10% off on your first order!',
                'description_ar' => 'احصل على خصم 10% على طلبك الأول!',
                'type' => 'percentage',
                'value' => 10.00,
                'condition' => [
                    'type' => Discount::CONDITION_FIRST_ORDER,
                ],
            ],
            [
                'code' => 'SAVE20',
                'status' => true,
                'start' => now(),
                'end' => now()->addMonths(3),
                'description_en' => 'Save 20% on orders over 100 SAR',
                'description_ar' => 'وفر 20% على الطلبات التي تزيد عن 100 ريال',
                'type' => 'percentage',
                'value' => 20.00,
                'condition' => [
                    'type' => Discount::CONDITION_MIN_AMOUNT,
                    'value' => 100,
                ],
            ],
            [
                'code' => 'BULK15',
                'status' => true,
                'start' => now(),
                'end' => now()->addMonths(4),
                'description_en' => '15% discount when buying 3 or more items',
                'description_ar' => 'خصم 15% عند شراء 3 عناصر أو أكثر',
                'type' => 'percentage',
                'value' => 15.00,
                'condition' => [
                    'type' => Discount::CONDITION_MIN_QUANTITY,
                    'value' => 3,
                ],
            ],
            [
                'code' => 'WELCOME5',
                'status' => true,
                'start' => now()->subDays(5),
                'end' => now()->addDays(30),
                'description_en' => 'Welcome discount for new customers',
                'description_ar' => 'خصم ترحيبي للعملاء الجدد',
                'type' => 'percentage',
                'value' => 5.00,
                'condition' => [
                    'type' => Discount::CONDITION_NEW_CUSTOMER,
                ],
            ],
            [
                'code' => 'FIXED50',
                'status' => true,
                'start' => now(),
                'end' => now()->addMonths(2),
                'description_en' => 'Get 50 SAR off on orders over 200 SAR',
                'description_ar' => 'احصل على خصم 50 ريال على الطلبات التي تزيد عن 200 ريال',
                'type' => 'fixed',
                'value' => 50.00,
                'condition' => [
                    'type' => Discount::CONDITION_MIN_AMOUNT,
                    'value' => 200,
                ],
            ],
            [
                'code' => 'SUMMER25',
                'status' => false,
                'start' => now()->addMonths(2),
                'end' => now()->addMonths(5),
                'description_en' => 'Summer sale - 25% off on selected items',
                'description_ar' => 'تخفيضات الصيف - خصم 25% على العناصر المختارة',
                'type' => 'percentage',
                'value' => 25.00,
                'condition' => null, // No condition
            ],
        ];

        foreach ($discounts as $discount) {
            Discount::updateOrCreate(
                ['code' => $discount['code']],
                $discount
            );
        }
    }
}
