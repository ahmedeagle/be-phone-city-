<?php

namespace App\Ai\Tools;

use App\Models\Category;
use App\Models\Product;
use App\Models\Offer;
use App\Models\User;

class SearchCatalogTool extends BaseTool
{
    public static function getName(): string
    {
        return 'search_catalog';
    }

    public static function getDefinition(): array
    {
        return [
            'name' => self::getName(),
            'description' => 'Search for products, categories, or offers in the catalog. Can search by name, filter by type, and limit results.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'Search query (product name, category name, etc.)',
                    ],
                    'type' => [
                        'type' => 'string',
                        'enum' => ['product', 'category', 'offer', 'all'],
                        'description' => 'Type of items to search for',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Maximum number of results to return (default: 5, max: 20)',
                        'minimum' => 1,
                        'maximum' => 20,
                    ],
                ],
                'required' => ['query', 'type'],
            ],
        ];
    }

    public function execute(array $arguments, ?User $user): array
    {
        $query = $arguments['query'] ?? '';
        $type = $arguments['type'] ?? 'all';
        $limit = min($arguments['limit'] ?? 5, 20);

        $results = [];

        try {
            if ($type === 'product' || $type === 'all') {
                $results['products'] = $this->searchProducts($query, $limit);
            }

            if ($type === 'category' || $type === 'all') {
                $results['categories'] = $this->searchCategories($query, $limit);
            }

            if ($type === 'offer' || $type === 'all') {
                $results['offers'] = $this->searchOffers($query, $limit);
            }

            return $this->success($results);
        } catch (\Exception $e) {
            return $this->error('Failed to search catalog: ' . $e->getMessage());
        }
    }

    protected function searchProducts(string $query, int $limit): array
    {
        $products = Product::where(function ($q) use ($query) {
            $q->where('name_en', 'like', "%{$query}%")
                ->orWhere('name_ar', 'like', "%{$query}%")
                ->orWhere('description_en', 'like', "%{$query}%")
                ->orWhere('description_ar', 'like', "%{$query}%");
        })
            ->limit($limit)
            ->get();

        return $products->map(function ($product) {
            $finalPrice = $product->getFinalPrice();
            $basePrice = $product->getBaseSellingPrice();

            return [
                'id' => $product->id,
                'name_en' => $product->name_en,
                'name_ar' => $product->name_ar,
                'description_en' => $product->description_en,
                'description_ar' => $product->description_ar,
                'price' => $basePrice,
                'final_price' => $finalPrice,
                'discount' => $basePrice - $finalPrice,
                'stock_status' => $product->stock_status,
                'quantity' => $product->quantity,
                'is_new' => $product->is_new,
                'is_featured' => $product->is_featured,
                'main_image' => $product->main_image,
            ];
        })->toArray();
    }

    protected function searchCategories(string $query, int $limit): array
    {
        $categories = Category::where(function ($q) use ($query) {
            $q->where('name_en', 'like', "%{$query}%")
                ->orWhere('name_ar', 'like', "%{$query}%");
        })
            ->limit($limit)
            ->get();

        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name_en' => $category->name_en,
                'name_ar' => $category->name_ar,
                'slug' => $category->slug,
                'image' => $category->image,
                'icon' => $category->icon,
                'is_trademark' => $category->is_trademark,
                'products_count' => $category->products()->count(),
            ];
        })->toArray();
    }

    protected function searchOffers(string $query, int $limit): array
    {
        $offers = Offer::active()
            ->where(function ($q) use ($query) {
                $q->where('name_en', 'like', "%{$query}%")
                    ->orWhere('name_ar', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        return $offers->map(function ($offer) {
            return [
                'id' => $offer->id,
                'name_en' => $offer->name_en,
                'name_ar' => $offer->name_ar,
                'slug' => $offer->slug,
                'value' => $offer->value,
                'type' => $offer->type,
                'apply_to' => $offer->apply_to,
                'start_at' => $offer->start_at?->toDateTimeString(),
                'end_at' => $offer->end_at?->toDateTimeString(),
                'image' => $offer->image,
            ];
        })->toArray();
    }
}
