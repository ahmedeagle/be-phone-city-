<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_products_list(): void
    {
        Product::factory(5)->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'original_price',
                            'final_price',
                        ],
                    ],
                    'pagination',
                ],
            ]);
    }

    public function test_can_filter_products_by_category(): void
    {
        $category = Category::factory()->create();
        $products = Product::factory(3)->create();
        foreach ($products as $product) {
            $product->categories()->attach($category->id);
        }
        Product::factory(2)->create(); // Different category

        $response = $this->getJson("/api/v1/products?category_id={$category->id}");

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(3, $data);
    }

    public function test_can_search_products_by_name(): void
    {
        Product::factory()->create([
            'name_en' => 'Special Product',
            'name_ar' => 'منتج خاص',
        ]);
        Product::factory()->create([
            'name_en' => 'Regular Product',
            'name_ar' => 'منتج عادي',
        ]);

        $response = $this->getJson('/api/v1/products?search=Special');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
    }

    public function test_can_filter_products_by_price_range(): void
    {
        Product::factory()->create(['main_price' => 100]);
        Product::factory()->create(['main_price' => 200]);
        Product::factory()->create(['main_price' => 300]);

        $response = $this->getJson('/api/v1/products?min_price=150&max_price=250');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
    }



    public function test_product_shows_best_offer_discount(): void
    {
        $product = Product::factory()->create(['main_price' => 100]);

        // Create a percentage offer (25% off)
        $offer1 = Offer::factory()->create([
            'type' => 'percentage',
            'value' => 25,
            'status' => 'active',
            'apply_to' => 'product',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);
        $offer1->offerables()->create([
            'offerable_id' => $product->id,
            'offerable_type' => Product::class,
        ]);

        // Create an amount offer (20 off)
        $offer2 = Offer::factory()->create([
            'type' => 'amount',
            'value' => 20,
            'status' => 'active',
            'apply_to' => 'product',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);
        $offer2->offerables()->create([
            'offerable_id' => $product->id,
            'offerable_type' => Product::class,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200);

        // Should apply 25% offer (25 off) as it's better than 20 off
        $finalPrice = $response->json('data.final_price');
        $this->assertEquals('75.00', $finalPrice);
    }
}
