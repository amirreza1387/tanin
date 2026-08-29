<?php

namespace App\Listeners;

use App\Events\CommentApproved;
use Illuminate\Support\Facades\Cache;

class InvalidateCommentCaches
{
    public function handle(CommentApproved $event): void
    {
        Cache::forget('article:'.$event->comment->article_id.':comments');
    }
}
