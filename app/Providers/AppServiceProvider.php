<?php

namespace App\Providers;

use App\Events\ArticlePublished;
use App\Events\ArticleUnpublished;
use App\Events\BreakingNewsChanged;
use App\Events\CommentApproved;
use App\Listeners\InvalidateArticleCaches;
use App\Listeners\InvalidateCommentCaches;
use App\Listeners\RefreshSitemap;
use App\Models\Article;
use App\Models\Advertisement;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Tag;
use App\Models\User;
use App\Policies\ArticlePolicy;
use App\Policies\AdvertisementPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CommentPolicy;
use App\Policies\MediaPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\TagRepositoryInterface;
use App\Repositories\EloquentArticleRepository;
use App\Repositories\EloquentCategoryRepository;
use App\Repositories\EloquentTagRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ArticleRepositoryInterface::class, EloquentArticleRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(TagRepositoryInterface::class, EloquentTagRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::policy(Advertisement::class, AdvertisementPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Event::listen([ArticlePublished::class, ArticleUnpublished::class], RefreshSitemap::class);
        Event::listen([ArticlePublished::class, ArticleUnpublished::class, BreakingNewsChanged::class], InvalidateArticleCaches::class);
        Event::listen(CommentApproved::class, InvalidateCommentCaches::class);
    }
}
