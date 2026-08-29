<?php

namespace App\Http\Requests;

class UpdateArticleRequest extends StoreArticleRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['prohibited'],
            'publish_at' => ['prohibited'],
        ]);
    }
}
