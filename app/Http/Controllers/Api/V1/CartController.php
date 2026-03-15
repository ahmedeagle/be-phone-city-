<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Http\Resources\CityResource;
use App\Models\Cart;
use App\Models\City;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Get cart items
     */
    public function index()
    {
        $cartItems = Cart::where('user_id', Auth::id())
            ->with(['product.images', 'product.categories', 'product.options', 'productOption.images'])
            ->get();

        $subtotal = $cartItems->sum('subtotal');

        $settings = Setting::getSettings();
        $taxPercentage = $settings->tax_percentage ?? 0;

        // Calculate tax as straight percentage of total
        $taxAmount = 0;
        if ($taxPercentage > 0) {
            $taxAmount = $subtotal * ($taxPercentage / 100);
        }

        $freeShippingThreshold = $settings->free_shipping_threshold ?? 0;

        // Fetch cities and force simple mode for the resource
        request()->merge(['simple' => true]);
        $shippingMethods = City::getAllActive();

        $paymentMethodsQuery = PaymentMethod::active();

        // If any product is in a bank transfer category, restrict to bank transfer payment methods only
        $hasBankTransferCategoryProduct = $cartItems->contains(function ($item) {
            return $item->product->categories->contains('is_bank_transfer', true);
        });

        if ($hasBankTransferCategoryProduct) {
            $paymentMethodsQuery->bankTransfer();
        } else {
            // Check if any product in cart does not support installments
            $allSupportInstallment = $cartItems->every(function ($item) {
                return $item->product->is_installment;
            });

            if (! $allSupportInstallment) {
                $paymentMethodsQuery->where('is_installment', false);
            }
        }

        $paymentMethods = $paymentMethodsQuery->get();

        // Total already includes tax because product prices do
        $total = $subtotal;

        return Response::success(
            __('Cart fetched successfully'),
            [
                'items' => CartResource::collection($cartItems),
                'summary' => [
                    'subtotal' => number_format($subtotal, 2),
                    // Tax is included in product prices, hiding from general view as per request
                    // 'tax' => number_format($taxAmount, 2),
                    // 'tax_percentage' => number_format($taxPercentage, 2),
                    'free_shipping_threshold' => number_format($freeShippingThreshold, 2),
                    'amount_needed_for_free_shipping' => $freeShippingThreshold > $subtotal
                        ? number_format($freeShippingThreshold - $subtotal, 2)
                        : 0,
                    'total' => number_format($total, 2),
                ],
                'shipping_methods' => CityResource::collection($shippingMethods),
                'payment_methods' => $paymentMethods->map(function ($method) {
                    return [
                        'id' => $method->id,
                        'name' => $method->name,
                        'name_en' => $method->name_en,
                        'name_ar' => $method->name_ar,
                        'image' => $method->image ? asset('storage/'.$method->image) : null,
                        'is_bank_transfer' => (bool) $method->is_bank_transfer,
                        'is_installment' => (bool) $method->is_installment,
                        'gateway' => $method->gateway ?? null,
                    ];
                }),
                'total' => $total,
                'count' => $cartItems->count(),
                'unique_products_count' => $cartItems->unique('product_id')->count(),
                'total_quantity' => $cartItems->sum('quantity'),
            ]
        );
    }

    /**
     * Add single item to cart
     * Can provide either product_id or product_option_id
     * If product_option_id is provided, product_id will be extracted from it
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|exists:products,id',
            'product_option_id' => 'nullable|exists:product_options,id',
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        // At least one of product_id or product_option_id must be provided
        if (! $request->product_id && ! $request->product_option_id) {
            return Response::error(
                __('Either product_id or product_option_id is required'),
                null,
                422
            );
        }

        $product = null;
        $productId = null;
        $price = 0;
        $availableQuantity = 0;
        $stockStatus = 'in_stock';

        // If product_option_id is provided, get product_id from it
        if ($request->product_option_id) {
            $option = ProductOption::with('product')->findOrFail($request->product_option_id);
            $product = $option->product;
            $productId = $product->id;
            $availableQuantity = $option->quantity;
            $stockStatus = $option->stock_status;

            // If product_id is also provided, verify they match
            if ($request->product_id && $request->product_id != $productId) {
                return Response::error(
                    __('Product option does not belong to the provided product'),
                    null,
                    422
                );
            }

            $price = $option->getFinalPrice();

        } else {
            // Only product_id provided
            $product = Product::with('options')->findOrFail($request->product_id);
            $productId = $product->id;
            $availableQuantity = $product->quantity;
            $stockStatus = $product->stock_status;

            // Check if product has options
            $hasOptions = $product->options()->exists();

            // If product has options, product_option_id is required
            if ($hasOptions) {
                return Response::error(
                    __('This product requires selecting an option'),
                    ['product_id' => $request->product_id],
                    422
                );
            }

            $price = $product->getFinalPrice();
        }

        // Check stock
        if ($stockStatus === 'out_of_stock' || $stockStatus === 'discontinued') {
            return Response::error(__('Product is out of stock'), null, 400);
        }

        if ($availableQuantity < $request->quantity) {
            return Response::error(__('Insufficient stock'), null, 400);
        }

        $userId = Auth::id();

        // Check if item already exists
        $cartItem = Cart::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('product_option_id', $request->product_option_id)
            ->first();

        if ($cartItem) {
            // Update quantity
            $newQuantity = $cartItem->quantity + $request->quantity;

            if ($availableQuantity < $newQuantity) {
                return Response::error(__('Insufficient stock'), null, 400);
            }

            $cartItem->update([
                'quantity' => $newQuantity,
                'price' => $price,
            ]);
        } else {
            // Create new cart item
            $cartItem = Cart::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'product_option_id' => $request->product_option_id,
                'quantity' => $request->quantity,
                'price' => $price,
            ]);
        }

        $cartItem->load(['product.images', 'product.categories', 'product.options', 'productOption.images']);

        return Response::success(
            __('Item added to cart successfully'),
            new CartResource($cartItem),
            201
        );
    }

    /**
     * Add multiple items to cart at once
     */
    public function storeMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_option_id' => 'nullable|exists:product_options,id',
            'items.*.quantity' => 'required|integer|min:1|max:99',
        ]);

        if ($validator->fails()) {
            return Response::error(__('Validation error'), $validator->errors(), 422);
        }

        $userId = Auth::id();
        $addedItems = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->items as $index => $item) {
                $product = Product::with('options')->find($item['product_id']);

                if (! $product) {
                    $errors[] = [
                        'index' => $index,
                        'product_id' => $item['product_id'],
                        'error' => __('Product not found'),
                    ];

                    continue;
                }

                // Check if product has options
                $hasOptions = $product->options()->exists();

                // If product has options, product_option_id is required
                if ($hasOptions && empty($item['product_option_id'])) {
                    $errors[] = [
                        'index' => $index,
                        'product_id' => $item['product_id'],
                        'error' => __('This product requires selecting an option'),
                    ];

                    continue;
                }

                // If product has no options, product_option_id should be null
                if (! $hasOptions && ! empty($item['product_option_id'])) {
                    $errors[] = [
                        'index' => $index,
                        'product_id' => $item['product_id'],
                        'error' => __('This product does not have options'),
                    ];

                    continue;
                }

                if (isset($item['product_option_id']) && $item['product_option_id']) {
                    $option = ProductOption::where('id', $item['product_option_id'])
                        ->where('product_id', $product->id)
                        ->first();

                    if (! $option) {
                        $errors[] = [
                            'index' => $index,
                            'product_id' => $item['product_id'],
                            'error' => __('Invalid option for this product'),
                        ];

                        continue;
                    }
                    $price = $option->getFinalPrice();
                    $availableQuantity = $option->quantity;
                } else {
                    $price = $product->getFinalPrice();
                    $availableQuantity = $product->quantity;
                }

                // Check stock
                $cartItem = Cart::where('user_id', $userId)
                    ->where('product_id', $item['product_id'])
                    ->where('product_option_id', $item['product_option_id'] ?? null)
                    ->first();

                if ($cartItem) {
                    // Update quantity
                    $newQuantity = $cartItem->quantity + $item['quantity'];

                    if ($availableQuantity < $newQuantity) {
                        $errors[] = [
                            'index' => $index,
                            'product_id' => $item['product_id'],
                            'error' => __('Insufficient stock for total quantity'),
                            'available' => $availableQuantity,
                            'in_cart' => $cartItem->quantity,
                        ];

                        continue;
                    }

                    $cartItem->update([
                        'quantity' => $newQuantity,
                        'price' => $price,
                    ]);
                } else {
                    if ($availableQuantity < $item['quantity']) {
                        $errors[] = [
                            'index' => $index,
                            'product_id' => $item['product_id'],
                            'error' => __('Insufficient stock'),
                            'available' => $availableQuantity,
                        ];

                        continue;
                    }
                    // Create new cart item
                    $cartItem = Cart::create([
                        'user_id' => $userId,
                        'product_id' => $item['product_id'],
                        'product_option_id' => $item['product_option_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'price' => $price,
                    ]);
                }

                $cartItem->load(['product.images', 'product.categories', 'product.options', 'productOption.images']);
                $addedItems[] = new CartResource($cartItem);
            }

            DB::commit();

            $responseData = [
                'added_items' => $addedItems,
                'added_count' => count($addedItems),
            ];

            if (! empty($errors)) {
                $responseData['errors'] = $errors;
                $responseData['error_count'] = count($errors);
            }

            $message = count($addedItems) > 0
                ? __('Items added to cart successfully')
                : __('No items were added to cart');

            return Response::success($message, $responseData, 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return Response::error(__('Failed to add items to cart'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        if ($validator->fails()) {
            return Response::error(__('Validation error'), $validator->errors(), 422);
        }

        $cartItem = Cart::where('user_id', Auth::id())
            ->with(['product.images', 'product.categories', 'productOption.images'])
            ->findOrFail($id);

        // Check stock
        $availableQuantity = $cartItem->productOption ? $cartItem->productOption->quantity : $cartItem->product->quantity;
        if ($availableQuantity < $request->quantity) {
            return Response::error(__('Insufficient stock'), null, 400);
        }

        $cartItem->update([
            'quantity' => $request->quantity,
            'price' => $cartItem->productOption ? $cartItem->productOption->getFinalPrice() : $cartItem->product->getFinalPrice(),
        ]);

        $cartItem->load(['product.images', 'product.categories', 'product.options', 'productOption.images']);

        return Response::success(
            __('Cart updated successfully'),
            new CartResource($cartItem)
        );
    }

    /**
     * Remove item from cart by cart ID
     */
    public function destroy(int $id)
    {
        $cartItem = Cart::where('user_id', Auth::id())->findOrFail($id);
        $cartItem->delete();

        return Response::success(__('Item removed from cart successfully'));
    }

    /**
     * Remove all cart items for a product (all options)
     * Deletes all cart items that match the product_id
     */
    public function removeByProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return Response::error(__('Validation error'), $validator->errors(), 422);
        }

        $deletedCount = Cart::where('user_id', Auth::id())
            ->where('product_id', $request->product_id)
            ->delete();

        if ($deletedCount === 0) {
            return Response::error(__('No items found for this product in cart'), null, 404);
        }

        return Response::success(
            __('All items for this product removed from cart successfully'),
        );
    }

    /**
     * Clear entire cart
     */
    public function clear()
    {
        Cart::where('user_id', Auth::id())->delete();

        return Response::success(__('Cart cleared successfully'));
    }
}
