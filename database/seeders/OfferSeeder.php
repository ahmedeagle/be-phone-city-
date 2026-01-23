<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        // Create global offers
        $globalOffer = Offer::factory()->create([
            'name_en' => 'Flash Sale',
            'name_ar' => 'تخفيضات سريعة',
            'apply_to' => 'all',
            'type' => 'percentage',
            'value' => 10,
            'status' => 'active',
        ]);

        // Create category-specific offers
        $categories = Category::whereNotNull('parent_id')->take(3)->get();
        foreach ($categories as $category) {
            $offer = Offer::factory()->create([
                'apply_to' => 'category',
                'status' => 'active',
            ]);

            // Attach to category via offerables
            $offer->offerables()->create([
                'offerable_id' => $category->id,
                'offerable_type' => Category::class,
            ]);
        }

        // Create product-specific offers
        $products = Product::inRandomOrder()->take(10)->get();
        foreach ($products as $product) {
            $offer = Offer::factory()->create([
                'apply_to' => 'product',
                'status' => 'active',
                'type' => fake()->randomElement(['amount', 'percentage']),
            ]);

            // Attach to product via offerables
            $offer->offerables()->create([
                'offerable_id' => $product->id,
                'offerable_type' => Product::class,
            ]);
        }

        // Create some inactive offers
        Offer::factory(5)->create([
            'status' => 'inactive',
        ]);
    }
}
