<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name_en' => fake()->words(3, true),
            'name_ar' => 'منتج ' . fake()->word(),
            'description_en' => fake()->paragraph(),
            'description_ar' => 'وصف المنتج ' . fake()->sentence(),
            'details_en' => fake()->paragraph(3),
            'details_ar' => 'تفاصيل المنتج ' . fake()->paragraph(),
            'about_en' => fake()->paragraph(2),
            'about_ar' => 'حول المنتج ' . fake()->sentence(),
            'capacity' => fake()->randomElement(['500ml', '1L', '250g', '1kg', null]),
            'points' => fake()->numberBetween(0, 100),
            'category_id' => Category::factory(),
            'main_price' => fake()->randomFloat(2, 10, 1000),
            'quantity' => fake()->numberBetween(0, 100),
            'is_new' => fake()->boolean(20),
            'is_new_arrival' => fake()->boolean(20),
            'is_featured' => fake()->boolean(15),
        ];
    }
}
