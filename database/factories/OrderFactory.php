<?php

namespace Database\Factories;

use App\Models\Discount;
use App\Models\Location;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 5000);
        $discountAmount = fake()->randomFloat(2, 0, $subtotal * 0.3); // Max 30% discount
        $shipping = fake()->randomFloat(2, 0, 50);
        $tax = $subtotal * 0.15; // 15% tax
        $pointsDiscount = fake()->randomFloat(2, 0, 50);
        $total = $subtotal - $discountAmount + $shipping + $tax - $pointsDiscount;

        $deliveryMethod = fake()->randomElement([Order::DELIVERY_HOME, Order::DELIVERY_STORE_PICKUP]);
        $locationId = ($deliveryMethod === Order::DELIVERY_HOME) ? Location::factory() : null;

        return [
            'user_id' => User::factory(),
            'notes' => fake()->optional()->sentence(),
            'location_id' => $locationId,
            'payment_method_id' => PaymentMethod::factory(),
            'delivery_method' => $deliveryMethod,
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'discount_id' => fake()->optional(0.3)->randomElement([null, Discount::factory()]),
            'shipping' => $shipping,
            'tax' => $tax,
            'points_discount' => $pointsDiscount,
            'total' => max(0, $total), // Ensure total is not negative
            'status' => fake()->randomElement([
                Order::STATUS_IN_PROGRESS,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
            ]),
        ];
    }
}
