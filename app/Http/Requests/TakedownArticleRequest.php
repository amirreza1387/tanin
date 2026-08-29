<?php

namespace App\Http\Requests;

class TakedownArticleRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:2000']];
    }
}
