<?php

namespace App\Http\Requests;

class UploadMediaRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'article_id' => ['nullable', 'integer', 'exists:articles,id'],
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,gif,mp4,mov,avi,webm',
                'max:102400',
            ],
        ];
    }
}
