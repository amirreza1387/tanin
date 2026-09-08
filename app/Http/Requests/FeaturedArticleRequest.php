<?php

namespace App\Http\Requests;

class FeaturedArticleRequest extends ApiRequest
{
    public function rules(): array { return ['is_featured' => ['required', 'boolean']]; }
}
