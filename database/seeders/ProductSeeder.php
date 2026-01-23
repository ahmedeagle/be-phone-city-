<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Image;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::whereNotNull('parent_id')->get();

        foreach ($categories as $category) {
            // Create 5-10 products per category
            $products = Product::factory(rand(5, 10))->create();

            // Attach products to category
            foreach ($products as $product) {
                $product->categories()->attach($category->id);
            }
        }
    }
}
