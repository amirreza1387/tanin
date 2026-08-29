<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Events\ArticlePublished;
use App\Events\ArticleUnpublished;
use App\Events\BreakingNewsChanged;
use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ArticleService
{
    public function __construct(private readonly SlugService $slugService) {}

    public function create(User $author, array $data): Article
    {
        return DB::transaction(function () use ($author, $data): Article {
            $article = new Article;
            $this->fill($article, $data, $author);
            $article->save();

            $this->syncTags($article, $data['tag_ids'] ?? []);
            $this->applyRequestedStatus($article, $data);

            return $article->fresh(['author', 'category', 'tags', 'featuredMedia']);
        });
    }

    public function update(Article $article, array $data): Article
    {
        return DB::transaction(function () use ($article, $data): Article {
            $this->fill($article, $data);
            $article->save();

            if (array_key_exists('tag_ids', $data)) {
                $this->syncTags($article, $data['tag_ids']);
            }

            return $article->fresh(['author', 'category', 'tags', 'featuredMedia']);
        });
    }

    public function publish(Article $article): Article
    {
        if ($article->status === ArticleStatus::PUBLISHED) {
            return $article;
        }

        $article->forceFill([
            'status' => ArticleStatus::PUBLISHED,
            'publish_at' => now(),
            'published_at' => now(),
            'takedown_note' => null,
        ])->save();

        ArticlePublished::dispatch($article->fresh());

        return $article->fresh(['author', 'category', 'tags', 'featuredMedia']);
    }

    public function schedule(Article $article, Carbon $publishAt): Article
    {
        if ($publishAt->isPast()) {
            throw new \InvalidArgumentException(__('messages.schedule_future'));
        }

        $article->forceFill([
            'status' => ArticleStatus::SCHEDULED,
            'publish_at' => $publishAt,
            'published_at' => null,
        ])->save();

        return $article->fresh(['author', 'category', 'tags', 'featuredMedia']);
    }

    public function revertToDraft(Article $article, ?string $note = null): Article
    {
        $article->forceFill([
            'status' => ArticleStatus::DRAFT,
            'publish_at' => null,
            'published_at' => null,
            'takedown_note' => $note,
            'is_breaking' => false,
        ])->save();

        ArticleUnpublished::dispatch($article->fresh());

        return $article->fresh(['author', 'category', 'tags', 'featuredMedia']);
    }

    public function setBreaking(Article $article, bool $isBreaking): Article
    {
        $article->update(['is_breaking' => $isBreaking]);
        BreakingNewsChanged::dispatch();

        return $article->fresh(['author', 'category', 'tags', 'featuredMedia']);
    }

    public function publishScheduled(): int
    {
        $count = 0;
        Article::query()
            ->where('status', ArticleStatus::SCHEDULED)
            ->where('publish_at', '<=', now())
            ->each(function (Article $article) use (&$count): void {
                $this->publish($article);
                $count++;
            });

        return $count;
    }

    private function fill(Article $article, array $data, ?User $author = null): void
    {
        if ($author !== null) {
            $article->author_id = $author->id;
        }

        $article->fill([
            'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $article->category_id,
            'featured_media_id' => array_key_exists('featured_media_id', $data) ? $data['featured_media_id'] : $article->featured_media_id,
            'title' => array_key_exists('title', $data) ? $data['title'] : $article->title,
            'lead' => array_key_exists('lead', $data) ? $data['lead'] : $article->lead,
            'body' => array_key_exists('body', $data) ? $data['body'] : $article->body,
            'meta_title' => array_key_exists('meta_title', $data) ? $data['meta_title'] : $article->meta_title,
            'meta_description' => array_key_exists('meta_description', $data) ? $data['meta_description'] : $article->meta_description,
            'canonical_path' => array_key_exists('canonical_path', $data) ? $data['canonical_path'] : $article->canonical_path,
        ]);

        $slugChanged = isset($data['title']) || $article->slug === null;
        if ($slugChanged) {
            $article->slug = $this->slugService->make(
                $data['title'] ?? $article->title,
                Article::class,
                $article->exists ? $article->id : null,
            );
        }

        if (array_key_exists('canonical_path', $data)) {
            $article->canonical_path = $data['canonical_path'];
        } elseif ($slugChanged || $article->canonical_path === null) {
            $article->canonical_path = '/articles/'.$article->slug;
        }
    }

    private function syncTags(Article $article, array $tagIds): void
    {
        $article->tags()->sync($tagIds);
    }

    private function applyRequestedStatus(Article $article, array $data): void
    {
        if (($data['status'] ?? null) === ArticleStatus::PUBLISHED->value) {
            $this->publish($article);
        } elseif (($data['status'] ?? null) === ArticleStatus::SCHEDULED->value && isset($data['publish_at'])) {
            $this->schedule($article, Carbon::parse($data['publish_at']));
        }
    }
}
