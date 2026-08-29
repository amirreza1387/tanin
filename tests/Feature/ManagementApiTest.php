<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('allows an admin to manage categories, tags, and users', function (): void {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $category = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/categories', ['name' => 'استان‌ها'])
        ->assertCreated()
        ->json('data');
    expect($category['slug'])->toBe('استان-ها');

    $tag = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/tags', ['name' => 'خبر ویژه'])
        ->assertCreated()
        ->json('data');
    expect($tag['slug'])->toBe('خبر-ویژه');

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/v1/management/users/{$user->id}", ['role' => 'reporter'])
        ->assertOk()
        ->assertJsonPath('data.role', 'reporter');

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/categories/{$category['id']}", ['name' => 'استان‌های جنوبی'])
        ->assertOk()
        ->assertJsonPath('data.name', 'استان‌های جنوبی');

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/tags/{$tag['id']}", ['name' => 'خبر ویژه جنوب'])
        ->assertOk()
        ->assertJsonPath('data.name', 'خبر ویژه جنوب');
});

it('prevents non-admins from managing categories, tags, users, and comment queues', function (): void {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();
    $comment = Comment::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/categories', ['name' => 'غیرمجاز'])
        ->assertForbidden();
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/tags', ['name' => 'غیرمجاز'])
        ->assertForbidden();
    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/management/users')
        ->assertForbidden();
    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/management/comments')
        ->assertForbidden();

    expect($category->exists)->toBeTrue()
        ->and($tag->exists)->toBeTrue()
        ->and($comment->exists)->toBeTrue();
});

it('uploads media and attaches it to an article for an authorized reporter', function (): void {
    Storage::fake('public');
    $reporter = User::factory()->reporter()->create();
    $article = Article::factory()->create(['author_id' => $reporter->id]);

    $response = $this->actingAs($reporter, 'sanctum')
        ->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('photo.jpg', 1200, 800),
            'article_id' => $article->id,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'image');
    expect($article->fresh()->media()->count())->toBe(1);
});

it('returns most viewed articles for today and this week', function (): void {
    $article = Article::factory()->published()->create();
    $article->viewStats()->create(['view_date' => today(), 'views' => 12]);

    $this->getJson('/api/v1/most-viewed?period=today')
        ->assertOk()
        ->assertJsonPath('data.0.id', $article->id);

    $this->getJson('/api/v1/most-viewed?period=invalid')
        ->assertStatus(422)
        ->assertJsonStructure(['data', 'meta', 'errors']);
});
