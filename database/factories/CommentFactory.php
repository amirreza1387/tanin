<?php

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'article_id' => Article::factory()->published(),
            'user_id' => User::factory(),
            'body' => 'نظر آزمایشی کاربر درباره این خبر.',
            'status' => CommentStatus::PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => CommentStatus::APPROVED]);
    }
}
