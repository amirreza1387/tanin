<?php

use App\Enums\ArticleStatus;
use App\Enums\Role;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleService;
use Illuminate\Support\Carbon;

it('lets a reporter publish and schedule their own article', function (): void {
    $reporter = User::factory()->reporter()->create();
    $article = Article::factory()->create(['author_id' => $reporter->id]);

    $this->actingAs($reporter, 'sanctum')
        ->postJson("/api/v1/articles/{$article->id}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', ArticleStatus::PUBLISHED->value);

    $scheduled = Article::factory()->create(['author_id' => $reporter->id]);
    $publishAt = Carbon::now()->addDay()->toIso8601String();

    $this->actingAs($reporter, 'sanctum')
        ->postJson("/api/v1/articles/{$scheduled->id}/schedule", ['publish_at' => $publishAt])
        ->assertOk()
        ->assertJsonPath('data.status', ArticleStatus::SCHEDULED->value);
});

it('prevents a reporter from changing another reporter article', function (): void {
    $reporter = User::factory()->reporter()->create();
    $otherArticle = Article::factory()->create([
        'author_id' => User::factory()->reporter()->create()->id,
    ]);

    $this->actingAs($reporter, 'sanctum')
        ->putJson("/api/v1/articles/{$otherArticle->id}", [
            'title' => 'تلاش غیرمجاز',
            'body' => 'متن',
        ])->assertForbidden();

    $this->actingAs($reporter, 'sanctum')
        ->postJson("/api/v1/articles/{$otherArticle->id}/publish")
        ->assertForbidden();

    $this->actingAs($reporter, 'sanctum')
        ->deleteJson("/api/v1/articles/{$otherArticle->id}")
        ->assertForbidden();
});

it('lets an admin revert any published article to draft', function (): void {
    $admin = User::factory()->admin()->create();
    $article = Article::factory()->published()->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/v1/articles/{$article->id}/revert", ['note' => 'نیازمند اصلاح'])
        ->assertOk()
        ->assertJsonPath('data.status', ArticleStatus::DRAFT->value)
        ->assertJsonPath('data.takedown_note', 'نیازمند اصلاح');
});

it('rejects article creation by a regular user', function (): void {
    $user = User::factory()->create(['role' => Role::USER]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/articles', [
            'title' => 'خبر',
            'body' => 'متن خبر',
        ])->assertForbidden();
});

it('publishes a scheduled article through the scheduler service', function (): void {
    $article = Article::factory()->scheduled()->create(['publish_at' => now()->subMinute()]);

    expect(app(ArticleService::class)->publishScheduled())->toBe(1)
        ->and($article->fresh()->status)->toBe(ArticleStatus::PUBLISHED);
});
