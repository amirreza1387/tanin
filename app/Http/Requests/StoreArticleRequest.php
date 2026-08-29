<?php

namespace App\Http\Requests;

use App\Enums\ArticleStatus;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'lead' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'canonical_path' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(array_column(ArticleStatus::cases(), 'value'))],
            'publish_at' => ['nullable', 'date', 'after:now', 'required_if:status,scheduled'],
        ];
    }
}
