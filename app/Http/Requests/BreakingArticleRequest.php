<?php

namespace App\Http\Requests;

class BreakingArticleRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['is_breaking' => ['required', 'boolean']];
    }
}
