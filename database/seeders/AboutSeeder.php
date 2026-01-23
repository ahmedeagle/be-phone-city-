<?php

namespace Database\Seeders;

use App\Models\About;
use Illuminate\Database\Seeder;

class AboutSeeder extends Seeder
{
    public function run(): void
    {
        // Create or update the single about record
        About::updateOrCreate(
            ['id' => 1], // Ensure only one record exists
            [
                'about_website_en' => 'Welcome to City Phone - Your trusted destination for the latest smartphones and accessories.',
                'about_website_ar' => 'مرحباً بكم في سيتي فون - وجهتكم الموثوقة لأحدث الهواتف الذكية والاكسسوارات.',
                'about_us_en' => 'City Phone is a leading retailer specializing in premium smartphones, accessories, and mobile technology. We are committed to providing our customers with the latest devices, exceptional service, and competitive prices.',
                'about_us_ar' => 'سيتي فون هو بائع تجزئة رائد متخصص في الهواتف الذكية المميزة والاكسسوارات وتكنولوجيا الهواتف المحمولة. نحن ملتزمون بتزويد عملائنا بأحدث الأجهزة وخدمة استثنائية وأسعار تنافسية.',
                'image' => null, // You can add an image path here
                'address_ar' => 'شارع الرئيسي، المدينة، الدولة',
                'address_en' => 'Main Street, City, Country',
                'maps' => 'https://www.google.com/maps/embed?pb=...', // Google Maps embed URL
                'email' => 'info@cityphone.com',
                'phone' => '+1234567890',
                'social_links' => [
                    [
                        'name' => 'Facebook',
                        'icon' => 'fa-brands fa-facebook',
                        'url' => 'https://facebook.com/cityphone',
                    ],
                    [
                        'name' => 'Twitter',
                        'icon' => 'fa-brands fa-twitter',
                        'url' => 'https://twitter.com/cityphone',
                    ],
                    [
                        'name' => 'Instagram',
                        'icon' => 'fa-brands fa-instagram',
                        'url' => 'https://instagram.com/cityphone',
                    ],
                    [
                        'name' => 'LinkedIn',
                        'icon' => 'fa-brands fa-linkedin',
                        'url' => 'https://linkedin.com/company/cityphone',
                    ],
                ],
            ]
        );
    }
}
