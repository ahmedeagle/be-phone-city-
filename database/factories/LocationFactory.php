<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'country' => fake()->country(),
            'city' => fake()->city(),
            'street_address' => fake()->streetAddress(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'label' => fake()->optional()->randomElement(['Home', 'Work', 'Office', 'Other']),
        ];
    }
}
