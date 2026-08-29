<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function moderate(User $user, Comment $comment): bool
    {
        return $user->isAdmin();
    }

    public function moderateAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
