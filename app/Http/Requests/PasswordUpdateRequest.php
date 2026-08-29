<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class PasswordUpdateRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:sanctum'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
