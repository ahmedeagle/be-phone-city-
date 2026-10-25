<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class TikTokCatalogService
{
    public const HEADERS = ['sku_id', 'title', 'description', 'availability', 'condition', 'price', 'link', 'image_link', 'brand', 'sale_price', 'product_type'];

    // One entry per product page, matching the parent product IDs used by Pixel and Events API.
    public function row(Product $product, Collection $categories): array
    {
        $title = $this->text($product->name_ar ?: $product->name_en);
        $description = $this->text($product->description_ar ?: $product->description_en);
        $brand = '';
        foreach ($product->categories as $category) {
            $visited = [];
            while ($category && !isset($visited[$category->id])) {
                $visited[$category->id] = true;
                if ($category->is_trademark) {
                    $brand = $this->text($category->name_en ?: $category->name_ar);
                    break 2;
                }
                $category = $categories->get($category->parent_id);
            }
        }
        if (!$product->id || !$product->slug || !$title || !$description || !$brand) {
            throw new InvalidArgumentException('missing_id_slug_title_description_or_brand');
        }

        // The product page initially selects the first option, not the cheapest option.
        $option = $product->options->first();
        if ($option) {
            $option->setRelation('product', $product);
        }
        $original = $option ? $option->getOriginalPrice() : (float) $product->main_price;
        $price = $option ? $option->getFinalPrice() : $product->getFinalPrice();
        $quantity = $option ? $option->quantity : $product->quantity;
        if (!is_finite($price) || $price <= 0 || !is_finite($original) || $original <= 0) {
            throw new InvalidArgumentException('invalid_price');
        }
        $base = rtrim((string) config('app.frontend_url'), '/');
        $this->assertUrl($base);
        $image = trim((string) $product->main_image);
        if (!$image) {
            throw new InvalidArgumentException('missing_image');
        }
        $image = preg_match('~^https?://~i', $image) ? $image : asset('storage/'.ltrim($image, '/'));
        $this->assertUrl($image);
        if (!in_array(strtolower(pathinfo(parse_url($image, PHP_URL_PATH), PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true)) {
            throw new InvalidArgumentException('image_requires_jpg_or_png');
        }

        return [
            'sku_id' => (string) $product->id,
            'title' => $title,
            'description' => $description,
            'availability' => $quantity > 0 ? 'in stock' : 'out of stock',
            'condition' => $product->is_new ? 'new' : 'used',
            'price' => number_format(max($original, $price), 2, '.', '').' SAR',
            'link' => $base.'/ar/singleproduct/'.rawurlencode($product->slug),
            'image_link' => $image,
            'brand' => $brand,
            'sale_price' => $price < $original ? number_format($price, 2, '.', '').' SAR' : '',
            'product_type' => $this->text($product->categories->firstWhere('is_trademark', false)?->name_ar ?? ''),
        ];
    }

    private function text(?string $value): string
    {
        $value = html_entity_decode(strip_tags($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/[\p{C}\p{So}\s]+/u', ' ', $value) ?? '');
    }

    private function assertUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new InvalidArgumentException('invalid_public_url');
        }
    }
}
