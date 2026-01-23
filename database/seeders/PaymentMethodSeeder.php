<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name_en' => 'Cash on Delivery',
                'name_ar' => 'الدفع عند الاستلام',
                'description_en' => 'Pay with cash when you receive your order.',
                'description_ar' => 'ادفع نقداً عند استلام طلبك.',
                'processing_fee_percentage' => 0.0,
                'status' => 'active',
                'gateway' => 'cash',
                'gateway_config' => null,
                'test_mode' => false,
                'image' => null,
            ],
            [
                'name_en' => 'Bank Transfer',
                'name_ar' => 'تحويل بنكي',
                'description_en' => 'Transfer payment to our bank account and upload proof.',
                'description_ar' => 'حوّل المبلغ إلى حسابنا البنكي وقم برفع إثبات الدفع.',
                'processing_fee_percentage' => 0.0,
                'status' => 'active',
                'gateway' => 'bank_transfer',
                'gateway_config' => null,
                'test_mode' => false,
                'image' => null,
            ],
            [
                'name_en' => 'Tamara',
                'name_ar' => 'تمارا',
                'description_en' => 'Buy now, pay later via Tamara.',
                'description_ar' => 'اشتر الآن وادفع لاحقًا عبر تمارا.',
                'processing_fee_percentage' => 0.0,
                'status' => 'inactive',
                'gateway' => 'tamara',
                'gateway_config' => null,
                'test_mode' => true,
                'image' => null,
            ],
            [
                'name_en' => 'Tabby',
                'name_ar' => 'تابي',
                'description_en' => 'Split your payment with Tabby.',
                'description_ar' => 'قسّم دفعتك عبر تابي.',
                'processing_fee_percentage' => 0.0,
                'status' => 'inactive',
                'gateway' => 'tabby',
                'gateway_config' => null,
                'test_mode' => true,
                'image' => null,
            ],
            [
                'name_en' => 'Amwal',
                'name_ar' => 'أموال',
                'description_en' => 'Fast and secure payment through Amwal.',
                'description_ar' => 'دفع سريع وآمن عبر أموال.',
                'processing_fee_percentage' => 0.0,
                'status' => 'inactive',
                'gateway' => 'amwal',
                'gateway_config' => null,
                'test_mode' => true,
                'image' => null,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['gateway' => $method['gateway']],
                $method
            );
        }
    }
}
