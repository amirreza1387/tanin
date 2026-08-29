<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['سیاسی', 'اقتصادی', 'ورزشی', 'فرهنگی', 'اجتماعی']);

        return [
            'name' => $name,
            'slug' => fake()->unique()->slug(),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
