<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class AdvertisementRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'placement' => ['required', Rule::in(['sidebar', 'horizontal', 'footer'])],
            'media_id' => ['nullable', 'integer', 'exists:media,id'],
            'link_url' => ['nullable', 'url', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
