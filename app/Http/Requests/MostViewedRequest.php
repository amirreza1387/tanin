<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class MostViewedRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'period' => ['sometimes', Rule::in(['today', 'week'])],
        ];
    }
}
