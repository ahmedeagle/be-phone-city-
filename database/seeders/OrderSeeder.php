<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users, payment methods, and discounts
        $users = User::all();
        $paymentMethods = PaymentMethod::all();
        $discounts = Discount::where('status', true)->get();

        if ($users->isEmpty() || $paymentMethods->isEmpty()) {
            $this->command->warn('Please seed Users and PaymentMethods first!');
            return;
        }

        // Create 20 orders
        for ($i = 0; $i < 20; $i++) {
            DB::beginTransaction();
            try {
                // Select random user
                $user = $users->random();

                // Get or create location for user (if home delivery)
                $deliveryMethod = fake()->randomElement([Order::DELIVERY_HOME, Order::DELIVERY_STORE_PICKUP]);
                $location = null;
                if ($deliveryMethod === Order::DELIVERY_HOME) {
                    $location = Location::firstOrCreate(
                        ['user_id' => $user->id],
                        [
                            'first_name' => $user->name,
                            'last_name' => fake()->lastName(),
                            'country' => fake()->country(),
                            'city' => fake()->city(),
                            'street_address' => fake()->streetAddress(),
                            'phone' => $user->phone,
                            'email' => $user->email,
                            'label' => fake()->randomElement(['Home', 'Work', 'Office']),
                        ]
                    );
                }

                // Calculate order totals
                $subtotal = 0;
                $items = [];

                // Create 1-5 items per order
                $itemCount = fake()->numberBetween(1, 5);

                for ($j = 0; $j < $itemCount; $j++) {
                    // Get random product
                    $product = Product::inRandomOrder()->first();
                    if (!$product) {
                        continue;
                    }

                    // Check if product has options
                    $productOption = null;
                    $hasOptions = $product->options()->exists();

                    if ($hasOptions) {
                        $productOption = $product->options()->inRandomOrder()->first();
                        $price = $productOption->getFinalPrice();
                    } else {
                        $price = $product->getFinalPrice();
                    }

                    $quantity = fake()->numberBetween(1, 3);
                    $itemTotal = $price * $quantity;
                    $subtotal += $itemTotal;

                    $items[] = [
                        'product_id' => $product->id,
                        'product_option_id' => $productOption?->id,
                        'price' => $price,
                        'quantity' => $quantity,
                        'total' => $itemTotal,
                    ];
                }

                if (empty($items)) {
                    DB::rollBack();
                    continue;
                }

                // Calculate discount
                $discount = null;
                $discountAmount = 0;
                $discountId = null;

                if (fake()->boolean(40) && $discounts->isNotEmpty()) {
                    $discount = $discounts->random();
                    $discountId = $discount->id;

                    if ($discount->type === 'percentage') {
                        $discountAmount = $subtotal * ($discount->value / 100);
                    } else {
                        $discountAmount = min($discount->value, $subtotal);
                    }
                }

                // Calculate other amounts
                $shipping = ($deliveryMethod === Order::DELIVERY_HOME) ? fake()->randomFloat(2, 10, 50) : 0;
                $tax = $subtotal * 0.15; // 15% tax
                $paymentMethod = $paymentMethods->random();
                $paymentMethodAmount = 0;
                $pointsDiscount = fake()->randomFloat(2, 0, 50);

                // Calculate total
                $total = $subtotal - $discountAmount + $shipping + $tax + $paymentMethodAmount - $pointsDiscount;
                $total = max(0, $total); // Ensure total is not negative

                // Create order
                $order = Order::create([
                    'user_id' => $user->id,
                    'notes' => fake()->optional(0.3)->sentence(),
                    'location_id' => $location?->id,
                    'payment_method_id' => $paymentMethod->id,
                    'delivery_method' => $deliveryMethod,
                    'subtotal' => $subtotal,
                    'discount' => $discountAmount,
                    'discount_id' => $discountId,
                    'shipping' => $shipping,
                    'tax' => $tax,
                    'payment_method_amount' => $paymentMethodAmount,
                    'points_discount' => $pointsDiscount,
                    'total' => $total,
                    'status' => fake()->randomElement([
                        Order::STATUS_IN_PROGRESS,
                        Order::STATUS_COMPLETED,
                        Order::STATUS_CANCELLED,
                    ]),
                ]);

                // Create order items
                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'product_option_id' => $item['product_option_id'],
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'total' => $item['total'],
                    ]);
                }

                // Create invoice for completed orders (80% chance)
                if ($order->status === Order::STATUS_COMPLETED && fake()->boolean(80)) {
                    $order->createInvoice(fake()->optional()->sentence());
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error('Error creating order: ' . $e->getMessage());
            }
        }

        $this->command->info('Orders with items and invoices created successfully!');
    }
}
