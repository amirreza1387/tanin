<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'author_id' => User::factory()->reporter(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => fake()->unique()->slug(),
            'lead' => 'این خلاصه‌ای از یک خبر فارسی برای آزمایش سامانه خبری است.',
            'body' => 'متن کامل خبر برای استفاده در محیط توسعه و آزمون.',
            'status' => ArticleStatus::DRAFT,
            'meta_title' => $title,
            'meta_description' => 'توضیحات سئوی خبر.',
            'views' => 0,
            'is_breaking' => false,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => ArticleStatus::PUBLISHED,
            'publish_at' => Carbon::now()->subHour(),
            'published_at' => Carbon::now()->subHour(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state([
            'status' => ArticleStatus::SCHEDULED,
            'publish_at' => Carbon::now()->addDay(),
            'published_at' => null,
        ]);
    }
}
