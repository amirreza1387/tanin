<?php

namespace App\Http\Resources;

use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $publishedAt = $this->published_at ?? $this->publish_at;
        $canonicalPath = $this->canonical_path ?: '/articles/'.$this->slug;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'lead' => $this->lead,
            'body' => $this->when($request->routeIs('articles.show', 'management.articles.*'), $this->body),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'publish_at' => $this->publish_at?->toIso8601String(),
            'publish_at_jalali' => JalaliDate::format($this->publish_at),
            'published_at' => $publishedAt?->toIso8601String(),
            'published_at_jalali' => JalaliDate::format($publishedAt),
            'views' => $this->views,
            'is_breaking' => $this->is_breaking,
            'is_featured' => $this->is_featured,
            'takedown_note' => $this->when($request->user()?->isAdmin(), $this->takedown_note),
            'seo' => [
                'meta_title' => $this->meta_title ?: $this->title,
                'meta_description' => $this->meta_description ?: $this->lead,
                'canonical_path' => $canonicalPath,
                'json_ld' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'NewsArticle',
                    'headline' => $this->title,
                    'description' => $this->meta_description ?: $this->lead,
                    'datePublished' => $publishedAt?->toIso8601String(),
                    'dateModified' => $this->updated_at?->toIso8601String(),
                    'mainEntityOfPage' => url($canonicalPath),
                    'author' => $this->whenLoaded('author', fn () => [
                        '@type' => 'Person',
                        'name' => $this->author?->name,
                    ]),
                    'image' => $this->when($this->relationLoaded('featuredMedia') && $this->featuredMedia, fn () => [
                        Storage::disk($this->featuredMedia->disk)->url($this->featuredMedia->path),
                    ]),
                ],
            ],
            'author' => new UserResource($this->whenLoaded('author')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'featured_media' => new MediaResource($this->whenLoaded('featuredMedia')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
        ];
    }
}
