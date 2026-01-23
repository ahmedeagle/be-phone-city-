<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    /**
     * Get all favorites
     */
    public function index()
    {
        $favorites = Favorite::where('user_id', Auth::id())
            ->with(['product.images', 'product.categories'])
            ->get();

        return Response::success(
            __('Favorites fetched successfully'),
            [
                'items' => FavoriteResource::collection($favorites),
                'count' => $favorites->count(),
            ]
        );
    }

    /**
     * Add single product to favorites
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return Response::error(__('Validation error'), $validator->errors(), 422);
        }

        $userId = Auth::id();

        // Check if already favorited
        $exists = Favorite::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->exists();

        if ($exists) {
            return Response::error(__('Product already in favorites'), null, 400);
        }

        $favorite = Favorite::create([
            'user_id' => $userId,
            'product_id' => $request->product_id,
        ]);

        $favorite->load(['product.images', 'product.categories']);

        return Response::success(
            __('Product added to favorites successfully'),
            new FavoriteResource($favorite),
            201
        );
    }

    /**
     * Add multiple products to favorites at once
     */
    public function storeMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return Response::error(__('Validation error'), $validator->errors(), 422);
        }

        $userId = Auth::id();
        $addedFavorites = [];
        $alreadyExists = [];

        DB::beginTransaction();
        try {
            foreach ($request->product_ids as $productId) {
                // Check if already favorited
                $exists = Favorite::where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->exists();

                if ($exists) {
                    $alreadyExists[] = $productId;
                    continue;
                }

                $favorite = Favorite::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                ]);

                $favorite->load(['product.images', 'product.categories']);
                $addedFavorites[] = new FavoriteResource($favorite);
            }

            DB::commit();

            $responseData = [
                'added_favorites' => $addedFavorites,
                'added_count' => count($addedFavorites),
            ];

            if (!empty($alreadyExists)) {
                $responseData['already_favorited'] = $alreadyExists;
                $responseData['skipped_count'] = count($alreadyExists);
            }

            $message = count($addedFavorites) > 0
                ? __('Products added to favorites successfully')
                : __('No new products were added to favorites');

            return Response::success($message, $responseData, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return Response::error(__('Failed to add favorites'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove product from favorites
     */
    public function destroy(int $id)
    {
        $favorite = Favorite::where('user_id', Auth::id())->findOrFail($id);
        $favorite->delete();

        return Response::success(__('Product removed from favorites successfully'));
    }

    /**
     * Toggle favorite status (add if not exists, remove if exists)
     */
    public function toggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return Response::error(__('Validation error'), $validator->errors(), 422);
        }

        $userId = Auth::id();

        $favorite = Favorite::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return Response::success(
                __('Product removed from favorites successfully'),
                ['is_favorited' => false]
            );
        } else {
            $favorite = Favorite::create([
                'user_id' => $userId,
                'product_id' => $request->product_id,
            ]);

            $favorite->load(['product.images', 'product.categories']);

            return Response::success(
                __('Product added to favorites successfully'),
                [
                    'is_favorited' => true,
                    'favorite' => new FavoriteResource($favorite),
                ],
                201
            );
        }
    }

    /**
     * Clear all favorites
     */
    public function clear()
    {
        Favorite::where('user_id', Auth::id())->delete();

        return Response::success(__('Favorites cleared successfully'));
    }

    /**
     * Check if product is favorited
     */
    public function check(int $productId)
    {
        $isFavorited = Favorite::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->exists();

        return Response::success(
            __('Favorite status checked'),
            ['is_favorited' => $isFavorited]
        );
    }
}
