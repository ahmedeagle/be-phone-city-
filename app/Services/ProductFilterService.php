<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductFilterService
{
    /**
     * Apply base query with relationships and counts
     */
    public function baseQuery(): Builder
    {
        return Product::with(['images', 'options.images', 'categories', 'favorites', 'carts'])
            ->withCount(['reviews', 'options'])
            ->withAvg('reviews', 'rating')
            ->addSelect([
                'total_sold' => DB::table('order_items')
                    ->selectRaw('COALESCE(SUM(order_items.quantity), 0)')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->whereColumn('order_items.product_id', 'products.id')
                    ->where('orders.status', 'completed')
            ]);
    }

    /**
     * Apply all filters to the query
     */
    public function applyFilters(Builder $query, Request $request): Builder
    {
        // Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // Filter by is_new
        if ($request->filled('new')) {
            $query->where('is_new', $request->boolean('new'));
        }

        // Filter by is_new_arrival
        if ($request->filled('new_arrival')) {
            $query->where('is_new_arrival', $request->boolean('new_arrival'));
        }

        // Filter by is_featured
        if ($request->filled('featured')) {
            $query->where('is_featured', $request->boolean('featured'));
        }

        // Search by name
        if ($request->filled('search')) {
            $this->applySearchFilter($query, $request->search);
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where('main_price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('main_price', '<=', $request->max_price);
        }

        // Filter by has offer
        if ($request->filled('has_offer')) {
            $this->applyOfferFilter($query, $request->boolean('has_offer'));
        }

        // Filter by best seller
        if ($request->filled('best_seller')) {
            $this->applyBestSellerFilter($query, $request->boolean('best_seller'));
        }

        // Apply sorting
        $this->applySorting($query, $request);

        return $query;
    }

    /**
     * Apply search filter
     */
    public function applySearchFilter(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name_en', 'like', "%{$search}%")
                ->orWhere('name_ar', 'like', "%{$search}%");
        });
    }

    /**
     * Apply offer filter
     */
    public function applyOfferFilter(Builder $query, bool $hasOffer): Builder
    {
        return $query->where(function ($q) use ($hasOffer) {
            if ($hasOffer) {
                // Products WITH offers (product, category, or global)
                $q->whereHas('offers', function ($offerQuery) {
                    $this->applyActiveOfferFilter($offerQuery);
                })
                ->orWhereHas('categories.offers', function ($offerQuery) {
                    $this->applyActiveOfferFilter($offerQuery);
                })
                ->orWhere(function ($query) {
                    // Global offers apply_to = all
                    $query->whereExists(function ($sub) {
                        $sub->selectRaw(1)
                            ->from('offers')
                            ->where('apply_to', 'all')
                            ->where('status', 'active')
                            ->where(function ($q) {
                                $now = now();
                                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
                            })
                            ->where(function ($q) {
                                $now = now();
                                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
                            });
                    });
                });
            } else {
                // Products WITHOUT any offers
                $q->whereDoesntHave('offers', function ($offerQuery) {
                    $this->applyActiveOfferFilter($offerQuery);
                })
                ->whereDoesntHave('categories.offers', function ($offerQuery) {
                    $this->applyActiveOfferFilter($offerQuery);
                })
                ->whereDoesntHave('categories', function ($categoryQuery) {
                    $categoryQuery->whereExists(function ($sub) {
                        $sub->selectRaw(1)
                            ->from('offers')
                            ->where('apply_to', 'all')
                            ->where('status', 'active');
                    });
                });
            }
        });
    }

    /**
     * Apply active offer filter to query
     */
    private function applyActiveOfferFilter($query): void
    {
        $now = now();

        $query->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            });
    }

    /**
     * Apply best seller filter
     */
    public function applyBestSellerFilter(Builder $query, bool $isBestSeller): Builder
    {
        if ($isBestSeller) {
            // Only show products that have been sold (have completed orders)
            return $query->whereHas('orderItems', function ($orderItemQuery) {
                $orderItemQuery->whereHas('order', function ($orderQuery) {
                    $orderQuery->where('status', 'completed');
                });
            });
        }

        return $query;
    }

    /**
     * Apply sorting to query
     */
    public function applySorting(Builder $query, Request $request): Builder
    {
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Handle sorting by average rating
        if ($sortBy === 'average_rating') {
            return $query->orderBy('reviews_avg_rating', $sortOrder);
        }

        // Handle sorting by best seller (total quantity sold)
        if ($sortBy === 'best_seller') {
            return $query->orderBy('total_sold', $sortOrder);
        }

        // Handle other sortable columns
        if (in_array($sortBy, ['created_at', 'main_price', 'name_en', 'name_ar'])) {
            return $query->orderBy($sortBy, $sortOrder);
        }

        return $query;
    }
}
