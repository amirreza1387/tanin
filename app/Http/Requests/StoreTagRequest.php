<?php

namespace App\Http\Requests;

class StoreTagRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120']];
    }
}
