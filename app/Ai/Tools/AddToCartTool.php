<?php

namespace App\Ai\Tools;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AddToCartTool extends BaseTool
{
    public static function getName(): string
    {
        return 'add_to_cart';
    }

    public static function getDefinition(): array
    {
        return [
            'name' => self::getName(),
            'description' => 'Add a product to the user\'s shopping cart. Requires authentication. Use this when user wants to add a product to their cart.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'product_id' => [
                        'type' => 'integer',
                        'description' => 'ID of the product to add',
                    ],
                    'quantity' => [
                        'type' => 'integer',
                        'description' => 'Quantity to add (default: 1)',
                        'minimum' => 1,
                    ],
                    'product_option_id' => [
                        'type' => 'integer',
                        'description' => 'ID of product option/variant (if applicable)',
                    ],
                ],
                'required' => ['product_id'],
            ],
        ];
    }

    public function execute(array $arguments, ?User $user): array
    {
        if (!$this->requiresAuth($user)) {
            return $this->error('Authentication required to add items to cart');
        }

        $validator = Validator::make($arguments, [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
            'product_option_id' => 'nullable|integer|exists:product_options,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        try {
            $productId = $arguments['product_id'];
            $quantity = $arguments['quantity'] ?? 1;
            $productOptionId = $arguments['product_option_id'] ?? null;

            $product = Product::find($productId);

            if (!$product) {
                return $this->error('Product not found');
            }

            // Check stock availability
            if ($product->quantity < $quantity) {
                return $this->error("Insufficient stock. Available: {$product->quantity}");
            }

            // Determine price
            $price = $product->getBaseSellingPrice();

            if ($productOptionId) {
                $option = ProductOption::find($productOptionId);
                if ($option && $option->product_id == $productId) {
                    $price = $option->price;
                }
            }

            // Check if item already exists in cart
            $existingCart = Cart::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->where('product_option_id', $productOptionId)
                ->first();

            if ($existingCart) {
                $newQuantity = $existingCart->quantity + $quantity;

                if ($product->quantity < $newQuantity) {
                    return $this->error("Cannot add more. Maximum available: {$product->quantity}");
                }

                $existingCart->update([
                    'quantity' => $newQuantity,
                    'price' => $price,
                ]);

                $cart = $existingCart;
            } else {
                $cart = Cart::create([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'product_option_id' => $productOptionId,
                    'quantity' => $quantity,
                    'price' => $price,
                ]);
            }

            return $this->success([
                'cart_id' => $cart->id,
                'product_name' => $product->name_en,
                'quantity' => $cart->quantity,
                'price' => $cart->price,
                'subtotal' => $cart->subtotal,
                'message' => 'Product added to cart successfully',
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to add to cart: ' . $e->getMessage());
        }
    }
}
