<?php

namespace Database\Seeders;

use App\Models\Certificate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CertificateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $certificates = [
            [
                'name_en' => 'ISO 9001:2015',
                'name_ar' => 'ISO 9001:2015',
                'image' => 'assets/images/certificates/iso-9001.svg',
                'main_image' => 'assets/images/certificates/iso-9001-main.jpg',
            ],
            [
                'name_en' => 'Quality Management Certificate',
                'name_ar' => 'شهادة إدارة الجودة',
                'image' => 'assets/images/certificates/quality-management.svg',
                'main_image' => 'assets/images/certificates/quality-management-main.jpg',
            ],
            [
                'name_en' => 'Customer Satisfaction Award',
                'name_ar' => 'جائزة رضا العملاء',
                'image' => 'assets/images/certificates/customer-satisfaction.svg',
                'main_image' => 'assets/images/certificates/customer-satisfaction-main.jpg',
            ],
            [
                'name_en' => 'Best Service Provider',
                'name_ar' => 'أفضل مقدم خدمة',
                'image' => 'assets/images/certificates/best-service.svg',
                'main_image' => 'assets/images/certificates/best-service-main.jpg',
            ],
        ];

        foreach ($certificates as $certificate) {
            Certificate::create($certificate);
        }
    }
}
