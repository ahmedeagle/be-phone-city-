<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name_en' => fake()->words(2, true),
            'name_ar' => 'تصنيف ' . fake()->word(),
            'image' => null,
            'icon' => null,
            'parent_id' => null,
            'excludes_madfu' => false,
        ];
    }

    /**
     * A department where Madfu is not offered (the phones subtree).
     */
    public function excludesMadfu(): static
    {
        return $this->state(fn (array $attributes) => [
            'excludes_madfu' => true,
        ]);
    }
}
