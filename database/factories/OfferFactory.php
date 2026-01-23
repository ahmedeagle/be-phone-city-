<?php

namespace Database\Factories;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['amount', 'percentage']);

        return [
            'name_en' => fake()->words(3, true) . ' Offer',
            'name_ar' => 'عرض ' . fake()->word(),
            'value' => $type === 'percentage' ? fake()->numberBetween(5, 50) : fake()->randomFloat(2, 10, 200),
            'type' => $type,
            'status' => fake()->randomElement(['active', 'inactive']),
            'apply_to' => fake()->randomElement(['product', 'category', 'all']),
            'start_at' => fake()->boolean(70) ? now()->subDays(rand(1, 30)) : null,
            'end_at' => fake()->boolean(70) ? now()->addDays(rand(1, 60)) : null,
        ];
    }
}
