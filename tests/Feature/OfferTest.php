<?php

namespace Tests\Feature\Offer;

use App\Models\Offer;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_active_offers(): void
    {
        Offer::factory(3)->create(['status' => 'active']);
        Offer::factory(2)->create(['status' => 'inactive']);

        $response = $this->getJson('/api/v1/offers');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(3, $data);
    }

    public function test_can_fetch_single_offer_with_details(): void
    {
        $offer = Offer::factory()->create([
            'apply_to' => 'product',
            'status' => 'active',
        ]);

        $product = Product::factory()->create();
        $offer->offerables()->create([
            'offerable_id' => $product->id,
            'offerable_type' => Product::class,
        ]);

        $response = $this->getJson("/api/v1/offers/{$offer->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'value',
                    'type',
                    'apply_to',
                    'products',
                ],
            ]);
    }

    public function test_global_offer_applies_to_all_products(): void
    {
        $globalOffer = Offer::factory()->create([
            'apply_to' => 'all',
            'status' => 'active',
            'type' => 'percentage',
            'value' => 10,
        ]);

        $product = Product::factory()->create(['main_price' => 100]);

        $bestOffer = $product->getBestOffer();

        $this->assertNotNull($bestOffer);
        $this->assertEquals($globalOffer->id, $bestOffer->id);
        $this->assertEquals(90, $product->getFinalPrice());
    }

    public function test_category_offer_applies_to_category_products(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'main_price' => 100,
        ]);

        $categoryOffer = Offer::factory()->create([
            'apply_to' => 'category',
            'status' => 'active',
            'type' => 'amount',
            'value' => 30,
        ]);
        $categoryOffer->offerables()->create([
            'offerable_id' => $category->id,
            'offerable_type' => Category::class,
        ]);

        $bestOffer = $product->getBestOffer();

        $this->assertNotNull($bestOffer);
        $this->assertEquals(70, $product->getFinalPrice());
    }
}
