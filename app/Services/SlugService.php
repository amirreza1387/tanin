<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class SlugService
{
    public function make(string $value, string $modelClass, ?int $ignoreId = null): string
    {
        $slug = $this->normalize($value);
        $baseSlug = $slug !== '' ? $slug : 'مورد';
        $candidate = $baseSlug;
        $suffix = 2;

        while ($this->exists($modelClass, $candidate, $ignoreId)) {
            $candidate = $baseSlug.'-'.$suffix++;
        }

        return $candidate;
    }

    public function normalize(string $value): string
    {
        $value = str_replace(
            ['ي', 'ى', 'ك', 'ۀ', 'ة', '‌', '_'],
            ['ی', 'ی', 'ک', 'ه', 'ت', ' ', ' '],
            trim($value),
        );
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value) ?? $value;
        $value = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $value) ?? $value;
        $value = preg_replace('/[\s-]+/u', '-', $value) ?? $value;

        return trim($value, '-');
    }

    private function exists(string $modelClass, string $slug, ?int $ignoreId): bool
    {
        /** @var Model $model */
        $model = new $modelClass;

        return $model->newQuery()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where($model->getKeyName(), '!=', $ignoreId))
            ->exists();
    }
}
