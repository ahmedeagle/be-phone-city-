<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Services\ProductFilterService;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CategoryController extends Controller
{
    use PaginatesResponses;

    public function __construct(
        protected ProductFilterService $filterService
    ) {
    }

    /**
     * Get all categories (tree structure)
     */
    public function index(Request $request)
    {
        // Get top-level categories with children
        $categories = Category::whereNull('parent_id')
            ->with(['children', 'images'])
            ->withCount('products');

        // Filter by is_trademark
        if ($request->filled('is_trademark')) {
            $isTrademark = $request->boolean('is_trademark');
            $categories->where('is_trademark', $isTrademark);
        }

        $data = $this->paginateData($categories);
        $categories = CategoryResource::collection($data['data']);

        return Response::success(
            __('Categories fetched successfully'),
             $categories,
            200,
            $data['pagination']
        );
    }

    /**
     * Get all trademarks (categories with is_trademark = true)
     */
    public function trademarks(Request $request)
    {
        // Get all categories marked as trademarks
        $trademarks = Category::where('is_trademark', true)
            ->withCount('products');

        $data = $this->paginateData($trademarks);
        $trademarks = CategoryResource::collection($data['data']);

        return Response::success(
            __('Trademarks fetched successfully'),
            $trademarks,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single category with products
     */
    public function show(Request $request, Category $category)
    {
        $category->load(['children', 'images', 'parent'])
            ->loadCount('products');

        // Get products for this category with filters
        $products = $this->filterService->baseQuery()
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('categories.id', $category->id);
            });
        $products = $this->filterService->applyFilters($products, $request);

        $productsData = $this->paginateData($products);
        $products = ProductResource::collection($productsData['data']);

        return Response::success(
            __('Category fetched successfully'),
            [
                'category' => new CategoryResource($category),
                'products' => $products,
            ],
            200,
            $productsData['pagination']
        );
    }

    /**
     * Get all products in category and its children (recursive, avoiding infinite loops)
     */
    public function products(Request $request, Category $category)
    {
        $category->load('children');

        // Get all category IDs including children (recursive)
        $categoryIds = $this->getCategoryIdsWithChildren($category);

        // Get products from all categories with base query
        $products = $this->filterService->baseQuery()
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });

        // Apply all filters
        $products = $this->filterService->applyFilters($products, $request);

        $productsData = $this->paginateData($products);
        $products = ProductResource::collection($productsData['data']);

        return Response::success(
            __('Products fetched successfully'),
            [
                'category' => new CategoryResource($category),
                'products' => $products,
            ],
            200,
            $productsData['pagination']
        );
    }

    /**
     * Get all category IDs including children recursively (avoiding infinite loops)
     */
    private function getCategoryIdsWithChildren(Category $category, array $visited = []): array
    {
        $categoryIds = [$category->id];
        $visited[] = $category->id;

        // Get direct children
        $children = Category::where('parent_id', $category->id)->get();

        foreach ($children as $child) {
            // Avoid infinite loops by checking if we've already visited this category
            if (!in_array($child->id, $visited)) {
                $childIds = $this->getCategoryIdsWithChildren($child, $visited);
                $categoryIds = array_merge($categoryIds, $childIds);
                $visited = array_merge($visited, $childIds);
            }
        }

        return array_unique($categoryIds);
    }
}
