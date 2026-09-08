<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PublicContentCache
{
    public function remember(string $namespace, array $parameters, int $seconds, callable $callback): mixed
    {
        ksort($parameters);

        return Cache::remember(
            "public:{$namespace}:".sha1(json_encode($parameters)),
            $seconds,
            $callback,
        );
    }

    public function forgetArticleLists(): void
    {
        // Versioning invalidates every parameterized public-list key without cache tags.
        Cache::increment('public:articles:version');
    }

    public function version(): int
    {
        return (int) Cache::get('public:articles:version', 1);
    }
}
