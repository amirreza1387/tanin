<?php

namespace App\Http\Requests;

class ScheduleArticleRequest extends ApiRequest
{
    public function rules(): array
    {
        return ['publish_at' => ['required', 'date', 'after:now']];
    }
}
