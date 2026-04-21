<?php

use App\Models\PaymentMethod;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PaymentMethod::updateOrCreate(
            ['gateway' => 'emkan'],
            [
                'name_en' => 'Emkan',
                'name_ar' => 'إمكان',
                'description_en' => 'Sharia-compliant Buy-Now-Pay-Later via Emkan.',
                'description_ar' => 'تقسيط متوافق مع الشريعة عبر إمكان.',
                'processing_fee_percentage' => 0.0,
                'status' => 'inactive',
                'is_installment' => true,
                'is_bank_transfer' => false,
                'is_madfu' => false,
                'image' => null,
            ]
        );

        PaymentMethod::updateOrCreate(
            ['gateway' => 'madfu'],
            [
                'name_en' => 'Madfu',
                'name_ar' => 'مدفوع',
                'description_en' => 'Split your payment with Madfu.',
                'description_ar' => 'قسّم دفعتك عبر مدفوع.',
                'processing_fee_percentage' => 0.0,
                'status' => 'inactive',
                'is_installment' => true,
                'is_bank_transfer' => false,
                'is_madfu' => true,
                'image' => null,
            ]
        );
    }

    public function down(): void
    {
        PaymentMethod::whereIn('gateway', ['emkan', 'madfu'])->delete();
    }
};
