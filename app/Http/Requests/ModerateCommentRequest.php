<?php

namespace App\Http\Requests;

use App\Enums\CommentStatus;
use Illuminate\Validation\Rule;

class ModerateCommentRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['status' => ['required', Rule::in(array_column(CommentStatus::cases(), 'value'))]];
    }
}
