<?php

namespace App\Ai\Tools;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AddToFavoriteTool extends BaseTool
{
    public static function getName(): string
    {
        return 'add_to_favorite';
    }

    public static function getDefinition(): array
    {
        return [
            'name' => self::getName(),
            'description' => 'Add a product to the user\'s favorites/wishlist. Requires authentication. Use this when user wants to save a product for later.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'product_id' => [
                        'type' => 'integer',
                        'description' => 'ID of the product to add to favorites',
                    ],
                ],
                'required' => ['product_id'],
            ],
        ];
    }

    public function execute(array $arguments, ?User $user): array
    {
        if (!$this->requiresAuth($user)) {
            return $this->error('Authentication required to add items to favorites');
        }

        $validator = Validator::make($arguments, [
            'product_id' => 'required|integer|exists:products,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        try {
            $productId = $arguments['product_id'];

            $product = Product::find($productId);

            if (!$product) {
                return $this->error('Product not found');
            }

            // Check if already in favorites
            $existingFavorite = Favorite::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            if ($existingFavorite) {
                return $this->success([
                    'message' => 'Product is already in your favorites',
                    'product_name' => $product->name_en,
                    'already_exists' => true,
                ]);
            }

            $favorite = Favorite::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);

            return $this->success([
                'favorite_id' => $favorite->id,
                'product_name' => $product->name_en,
                'message' => 'Product added to favorites successfully',
                'already_exists' => false,
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to add to favorites: ' . $e->getMessage());
        }
    }
}
