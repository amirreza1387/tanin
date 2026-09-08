<?php

namespace App\Http\Controllers;

use App\Models\Article;

class PublicArticleController extends Controller
{
    public function __invoke(string $slug)
    {
        $article = Article::query()
            ->with(['author', 'category', 'featuredMedia'])
            ->where('status', 'published')
            ->where('slug', $slug)
            ->firstOrFail();

        return view('article', compact('article'));
    }
}
