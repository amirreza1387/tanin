<?php

namespace App\Events;

use App\Models\Article;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArticleUnpublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Article $article) {}
}
