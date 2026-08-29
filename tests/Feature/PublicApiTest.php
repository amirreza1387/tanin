<?php

use App\Enums\CommentStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\User;

it('lists and shows published articles with SEO fields', function (): void {
    $article = Article::factory()->published()->create();

    $this->getJson('/api/v1/articles')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta', 'errors']);

    $this->getJson("/api/v1/articles/{$article->slug}")
        ->assertOk()
        ->assertJsonPath('data.seo.json_ld.@type', 'NewsArticle')
        ->assertJsonPath('data.seo.canonical_path', "/articles/{$article->slug}");
});

it('searches published articles on SQLite using LIKE fallback', function (): void {
    Article::factory()->published()->create([
        'title' => 'خبر ویژه خوزستان',
        'lead' => 'گزارش تازه',
    ]);

    $this->getJson('/api/v1/search?q=خوزستان')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns only approved comments and accepts authenticated comments', function (): void {
    $article = Article::factory()->published()->create();
    $approved = Comment::factory()->approved()->create(['article_id' => $article->id]);
    Comment::factory()->create(['article_id' => $article->id]);

    $this->getJson("/api/v1/articles/{$article->id}/comments")
        ->assertOk()
        ->assertJsonPath('data.0.id', $approved->id);

    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/articles/{$article->id}/comments", ['body' => 'نظر جدید'])
        ->assertCreated()
        ->assertJsonPath('data.status', CommentStatus::PENDING->value);
});

it('allows only admins to moderate comments', function (): void {
    $comment = Comment::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/v1/management/comments/{$comment->id}", ['status' => 'approved'])
        ->assertForbidden();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/management/comments/{$comment->id}", ['status' => 'approved'])
        ->assertOk()
        ->assertJsonPath('data.status', CommentStatus::APPROVED->value);
});

it('returns category trees and cached breaking news', function (): void {
    $category = Category::factory()->create();
    Article::factory()->published()->create([
        'category_id' => $category->id,
        'is_breaking' => true,
    ]);

    $this->getJson('/api/v1/categories')->assertOk();
    $this->getJson('/api/v1/breaking-news')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
