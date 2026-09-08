<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#2C3E50">
        <title>{{ $article->meta_title ?: $article->title }} | طنین جنوب</title>
        <meta name="description" content="{{ $article->meta_description ?: $article->lead }}">
        <link rel="canonical" href="{{ url($article->canonical_path ?: '/articles/'.$article->slug) }}">
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $article->title }}">
        <meta property="og:description" content="{{ $article->meta_description ?: $article->lead }}">
        @if ($article->featuredMedia)
            <meta property="og:image" content="{{ asset('storage/'.$article->featuredMedia->path) }}">
        @endif
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div id="app">
            <main class="mx-auto max-w-5xl px-4 py-8">
                <article class="rounded-xl bg-white p-5 shadow-sm sm:p-8">
                    <p class="text-sm font-bold text-brand-red">{{ $article->category?->name ?: 'خبر' }}</p>
                    <h1 class="mt-3 text-2xl font-black leading-[1.8] sm:text-4xl">{{ $article->title }}</h1>
                    <p class="mt-4 text-sm text-muted">
                        {{ $article->author?->name ?: 'تحریریه' }} · {{ optional($article->published_at)->format('Y/m/d') }}
                    </p>
                    @if ($article->featuredMedia)
                        <img class="mt-6 aspect-video w-full rounded-xl object-cover" src="{{ asset('storage/'.$article->featuredMedia->path) }}" alt="{{ $article->title }}">
                    @endif
                    @if ($article->lead)
                        <p class="mt-7 border-r-4 border-brand-red pr-4 font-bold leading-9">{{ $article->lead }}</p>
                    @endif
                    <div class="mt-7 whitespace-pre-line leading-[2.2]">{{ $article->body }}</div>
                </article>
            </main>
        </div>
    </body>
</html>
