<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Support\Facades\File;

class SitemapService
{
    public function refresh(): void
    {
        $urls = collect()
            ->merge(Article::query()->where('status', 'published')->get()->map(fn (Article $article) => [
                'loc' => url('/api/v1/articles/'.$article->slug),
                'lastmod' => optional($article->updated_at)->toAtomString(),
            ]))
            ->merge(Category::query()->get()->map(fn (Category $category) => [
                'loc' => url('/api/v1/categories/'.$category->slug),
                'lastmod' => optional($category->updated_at)->toAtomString(),
            ]))
            ->merge(Tag::query()->get()->map(fn (Tag $tag) => [
                'loc' => url('/api/v1/tags/'.$tag->slug),
                'lastmod' => optional($tag->updated_at)->toAtomString(),
            ]));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;
        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>';
            if ($url['lastmod']) {
                $xml .= '<lastmod>'.$url['lastmod'].'</lastmod>';
            }
            $xml .= '</url>'.PHP_EOL;
        }
        $xml .= '</urlset>'.PHP_EOL;

        File::ensureDirectoryExists(public_path());
        File::put(public_path('sitemap.xml'), $xml);
    }
}
