<?php

namespace App\Http\Requests;

use App\Models\Comment;
use Illuminate\Validation\Validator;

class StoreCommentRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:3000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parentId = $this->input('parent_id');
            $parent = $parentId === null
                ? null
                : Comment::query()->find($parentId);
            $article = $this->route('article');

            if ($parent !== null && $parent->parent_id !== null) {
                $validator->errors()->add('parent_id', __('messages.comment_one_level'));
            }

            if ($parent !== null && $article !== null && $parent->article_id !== $article->id) {
                $validator->errors()->add('parent_id', __('messages.comment_same_article'));
            }
        });
    }
}
