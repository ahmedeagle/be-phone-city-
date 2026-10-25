<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Services\TikTokCatalogService;
use InvalidArgumentException;
use Tests\TestCase;

class TikTokCatalogTest extends TestCase
{
    private function product(): Product
    {
        $product = \Mockery::mock(Product::class)->makePartial();
        $product->forceFill([
            'id' => 1915, 'slug' => 'test-phone', 'name_ar' => 'هاتف اختبار',
            'description_ar' => '<p>هاتف، بشاشة كبيرة</p>',
            'main_image' => 'https://cityphonesa.com/storage/products/phone.jpg',
            'main_price' => 2000, 'quantity' => 3, 'is_new' => true,
        ]);
        $product->shouldReceive('getFinalPrice')->andReturn(1800.50);
        $brand = new Category(['name_en' => 'Example Brand', 'is_trademark' => true]);
        $brand->id = 10;
        $product->setRelation('categories', collect([$brand]));
        $product->setRelation('options', collect());
        config(['app.frontend_url' => 'https://cityphonesa.com']);
        return $product;
    }

    public function test_product_ids_match_tracking_and_public_prices_are_exported(): void
    {
        $row = (new TikTokCatalogService())->row($this->product(), collect());
        $this->assertSame('1915', $row['sku_id']);
        $this->assertSame('2000.00 SAR', $row['price']);
        $this->assertSame('1800.50 SAR', $row['sale_price']);
        $this->assertSame('هاتف، بشاشة كبيرة', $row['description']);
        $this->assertSame('in stock', $row['availability']);
        $this->assertSame('new', $row['condition']);
        $this->assertSame('https://cityphonesa.com/ar/singleproduct/test-phone', $row['link']);
        $this->assertArrayNotHasKey('purchase_price', $row);
    }

    public function test_default_option_price_and_stock_override_parent_values(): void
    {
        $product = $this->product();
        $option = \Mockery::mock(ProductOption::class)->makePartial();
        $option->quantity = 0;
        $option->shouldReceive('getOriginalPrice')->andReturn(2300.0);
        $option->shouldReceive('getFinalPrice')->andReturn(2100.0);
        $product->setRelation('options', collect([$option]));
        $product->is_new = false;
        $row = (new TikTokCatalogService())->row($product, collect());
        $this->assertSame('2300.00 SAR', $row['price']);
        $this->assertSame('2100.00 SAR', $row['sale_price']);
        $this->assertSame('out of stock', $row['availability']);
        $this->assertSame('used', $row['condition']);
    }

    public function test_missing_brand_is_reported_instead_of_invented(): void
    {
        $product = $this->product();
        $product->setRelation('categories', collect());
        $this->expectException(InvalidArgumentException::class);
        (new TikTokCatalogService())->row($product, collect());
    }

    public function test_brand_can_be_resolved_from_category_ancestor(): void
    {
        $product = $this->product();
        $brand = $product->categories->first();
        $child = new Category(['parent_id' => $brand->id, 'name_ar' => 'هواتف']);
        $child->id = 20;
        $product->setRelation('categories', collect([$child]));
        $row = (new TikTokCatalogService())->row($product, collect([$brand])->keyBy('id'));
        $this->assertSame('Example Brand', $row['brand']);
    }

    public function test_nonpositive_price_is_not_advertised(): void
    {
        $product = $this->product();
        $option = \Mockery::mock(ProductOption::class)->makePartial();
        $option->shouldReceive('getOriginalPrice')->andReturn(2000.0);
        $option->shouldReceive('getFinalPrice')->andReturn(0.0);
        $product->setRelation('options', collect([$option]));
        $this->expectException(InvalidArgumentException::class);
        (new TikTokCatalogService())->row($product, collect());
    }

    public function test_catalog_endpoint_is_unavailable_until_enabled(): void
    {
        config(['tiktok.catalog_enabled' => false]);
        $this->get('/feeds/tiktok-products.csv')->assertNotFound();
    }
}
