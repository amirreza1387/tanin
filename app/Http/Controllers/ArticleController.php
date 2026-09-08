<?php

namespace App\Http\Controllers;

use App\Http\Requests\BreakingArticleRequest;
use App\Http\Requests\FeaturedArticleRequest;
use App\Http\Requests\ScheduleArticleRequest;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\TakedownArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\Category;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Services\ArticleService;
use App\Services\ViewCounterService;
use App\Services\PublicContentCache;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articles,
        private readonly ArticleService $service,
    ) {}

    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);
        $cache = app(PublicContentCache::class);
        $parameters = array_filter([...$request->only(['category_id', 'category_slug', 'from', 'to', 'page']), 'per_page' => $perPage]);
        $parameters['v'] = $cache->version();
        $articles = $cache->remember('articles', $parameters, 120, fn () => $this->articles->published($request->only(['category_id', 'category_slug', 'from', 'to']), $perPage));

        return ApiResponse::paginated(ArticleResource::class, $articles);
    }

    public function show(string $slug)
    {
        $article = $this->articles->findPublishedBySlug($slug);
        app(ViewCounterService::class)->increment($article);

        return ApiResponse::success(new ArticleResource($article));
    }

    public function activeBreaking()
    {
        return ApiResponse::success(ArticleResource::collection(
            cache()->remember('articles:breaking', 60, fn () => $this->articles->breaking())
        ));
    }

    public function featured()
    {
        $article = Article::query()->with(['author', 'category', 'tags', 'featuredMedia'])
            ->where('status', 'published')->where('is_featured', true)->latest('updated_at')->first();

        return ApiResponse::success($article ? new ArticleResource($article) : null);
    }

    public function editorPicks()
    {
        $articles = Article::query()->with(['author', 'category', 'tags', 'featuredMedia'])
            ->where('status', 'published')
            ->whereHas('tags', fn ($query) => $query->where('name', 'پیشنهاد سردبیر'))
            ->latest('published_at')->limit(4)->get();

        return ApiResponse::success(ArticleResource::collection($articles));
    }

    public function homeSections()
    {
        $sections = Category::query()
            ->whereHas('articles', fn ($query) => $query->where('status', 'published')->whereNotNull('published_at'))
            ->orderBy('sort_order')->limit(4)->get()->map(function (Category $category) {
                $articles = Article::query()->with(['author', 'category', 'tags', 'featuredMedia'])
                    ->where('status', 'published')->where('category_id', $category->id)->latest('published_at')->limit(4)->get();
                return ['category' => ['name' => $category->name, 'slug' => $category->slug], 'articles' => ArticleResource::collection($articles)];
            });

        return ApiResponse::success($sections);
    }

    public function store(StoreArticleRequest $request)
    {
        $this->authorize('create', Article::class);
        $article = $this->service->create($request->user(), $request->validated());

        return ApiResponse::success(new ArticleResource($article), [], 201);
    }

    public function update(UpdateArticleRequest $request, Article $article)
    {
        $this->authorize('update', $article);
        $article = $this->service->update($article, $request->validated());
        app(PublicContentCache::class)->forgetArticleLists();

        return ApiResponse::success(new ArticleResource($article));
    }

    public function destroy(Article $article)
    {
        $this->authorize('delete', $article);
        $article->delete();
        app(PublicContentCache::class)->forgetArticleLists();

        return ApiResponse::success(['message' => __('messages.article_deleted')]);
    }

    public function publish(Article $article)
    {
        $this->authorize('publish', $article);
        $article = $this->service->publish($article);

        return ApiResponse::success(new ArticleResource($article));
    }

    public function schedule(ScheduleArticleRequest $request, Article $article)
    {
        $this->authorize('schedule', $article);
        $article = $this->service->schedule($article, now()->parse($request->validated('publish_at')));

        return ApiResponse::success(new ArticleResource($article));
    }

    public function revert(TakedownArticleRequest $request, Article $article)
    {
        $this->authorize('revert', $article);
        $article = $this->service->revertToDraft($article, $request->validated('note'));

        return ApiResponse::success(new ArticleResource($article));
    }

    public function breaking(BreakingArticleRequest $request, Article $article)
    {
        $this->authorize('breaking', $article);
        $article = $this->service->setBreaking($article, $request->boolean('is_breaking'));

        return ApiResponse::success(new ArticleResource($article));
    }

    public function featuredUpdate(FeaturedArticleRequest $request, Article $article)
    {
        $this->authorize('breaking', $article);
        $article = $this->service->setFeatured($article, $request->boolean('is_featured'));
        app(PublicContentCache::class)->forgetArticleLists();

        return ApiResponse::success(new ArticleResource($article));
    }

    public function management(Request $request)
    {
        $user = $request->user();
        $this->authorize('manage', Article::class);
        $query = $user->isAdmin()
            ? $this->articles->queryForManagement()
            : $this->articles->queryForManagement($user->id);
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        return ApiResponse::paginated(ArticleResource::class, $query->paginate($perPage));
    }
}
