<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_en' => fake()->words(2, true),
            'name_ar' => 'طريقة دفع ' . fake()->word(),
            'image' => null,
            'description_en' => fake()->optional()->sentence(),
            'description_ar' => null,
            'status' => 'active',
            'is_installment' => false,
            'is_bank_transfer' => false,
            'processing_fee_percentage' => 0,
        ];
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_bank_transfer' => true,
        ]);
    }
}
