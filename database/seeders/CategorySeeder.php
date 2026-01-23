<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Create parent categories
        $electronics = Category::create([
            'name_en' => 'Electronics',
            'name_ar' => 'إلكترونيات',
        ]);

        $clothing = Category::create([
            'name_en' => 'Clothing',
            'name_ar' => 'ملابس',
        ]);

        $home = Category::create([
            'name_en' => 'Home & Kitchen',
            'name_ar' => 'المنزل والمطبخ',
        ]);

        $beauty = Category::create([
            'name_en' => 'Beauty & Personal Care',
            'name_ar' => 'الجمال والعناية الشخصية',
        ]);

        // Create sub-categories for Electronics
        Category::create([
            'name_en' => 'Smartphones',
            'name_ar' => 'هواتف ذكية',
            'parent_id' => $electronics->id,
        ]);

        Category::create([
            'name_en' => 'Laptops',
            'name_ar' => 'أجهزة لابتوب',
            'parent_id' => $electronics->id,
        ]);

        Category::create([
            'name_en' => 'Headphones',
            'name_ar' => 'سماعات',
            'parent_id' => $electronics->id,
        ]);

        // Create sub-categories for Clothing
        Category::create([
            'name_en' => 'Men\'s Clothing',
            'name_ar' => 'ملابس رجالية',
            'parent_id' => $clothing->id,
        ]);

        Category::create([
            'name_en' => 'Women\'s Clothing',
            'name_ar' => 'ملابس نسائية',
            'parent_id' => $clothing->id,
        ]);

        Category::create([
            'name_en' => 'Kids\' Clothing',
            'name_ar' => 'ملابس أطفال',
            'parent_id' => $clothing->id,
        ]);

        // Create sub-categories for Home
        Category::create([
            'name_en' => 'Furniture',
            'name_ar' => 'أثاث',
            'parent_id' => $home->id,
        ]);

        Category::create([
            'name_en' => 'Kitchen Appliances',
            'name_ar' => 'أجهزة المطبخ',
            'parent_id' => $home->id,
        ]);
    }
}
