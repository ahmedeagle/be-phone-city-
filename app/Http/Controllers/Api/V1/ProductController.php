<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductView;
use App\Services\ProductFilterService;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ProductController extends Controller
{
    use PaginatesResponses;

    public function __construct(
        protected ProductFilterService $filterService
    ) {
    }

    /**
     * Get all products with filters
     */
    public function index(Request $request)
    {
        $query = $this->filterService->baseQuery();
        $query = $this->filterService->applyFilters($query, $request);

        $data = $this->paginateData($query);
        $products = ProductResource::collection($data['data']);

        return Response::success(__('Products fetched successfully'), $products, 200, $data['pagination']);
    }

    /**
     * Get single product details
     */
    public function show(Product $product)
    {
        // Track product view for authenticated users
        if (Auth::guard('sanctum')->check()) {
            $userId = Auth::guard('sanctum')->id();

            // Create or update product view record
            ProductView::updateOrCreate(
                [
                    'user_id' => $userId,
                    'product_id' => $product->id,
                ],
                [
                    'viewed_at' => now(),
                ]
            );
        }

        $product->load([
            'images',
            'options.images',
            'categories',
            'favorites',
            'carts',
            'reviews' => fn ($q) => $q->approved()->with('user'),
        ])
            ->loadCount([
                'reviews as reviews_count' => fn ($q) => $q->approved(),
                'options',
            ])
            ->loadAvg(['reviews as reviews_avg_rating' => fn ($q) => $q->approved()], 'rating');

        return Response::success(
            __('Product fetched successfully'),
            new ProductResource($product)
        );
    }

    /**
     * Get new arrivals products
     */
    public function newArrivals(Request $request)
    {
        $settings = \App\Models\Setting::getSettings();

        // Check if new arrivals section is enabled
        if (!$settings->show_new_arrivals_section) {
            return Response::success(
                __('New arrivals section is disabled'),
                [],
                200
            );
        }

        $count = $settings->new_arrivals_count ?? 10;
        $query = $this->filterService->baseQuery();
        $query->where('is_new_arrival', true)
            ->orderBy('created_at', 'desc')
            ->limit($count);

        $products = ProductResource::collection($query->get());

        return Response::success(
            __('New arrivals fetched successfully'),
            $products
        );
    }

    /**
     * Get featured products
     */
    public function featured(Request $request)
    {
        $settings = \App\Models\Setting::getSettings();

        // Check if featured section is enabled
        if (!$settings->show_featured_section) {
            return Response::success(
                __('Featured section is disabled'),
                [],
                200
            );
        }

        $count = $settings->featured_count ?? 10;
        $query = $this->filterService->baseQuery();
        $query->where('is_featured', true)
            ->orderBy('created_at', 'desc')
            ->limit($count);

        $products = ProductResource::collection($query->get());

        return Response::success(
            __('Featured products fetched successfully'),
            $products
        );
    }
}
