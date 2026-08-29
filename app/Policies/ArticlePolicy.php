<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function manage(User $user): bool
    {
        return $user->isAdmin() || $user->isReporter();
    }

    public function view(?User $user, Article $article): bool
    {
        return $article->status->value === 'published'
            || ($user !== null && ($user->isAdmin() || $article->author_id === $user->id));
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isReporter();
    }

    public function update(User $user, Article $article): bool
    {
        return $user->isAdmin() || ($user->isReporter() && $article->author_id === $user->id);
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->isAdmin() || ($user->isReporter() && $article->author_id === $user->id);
    }

    public function publish(User $user, Article $article): bool
    {
        return $user->isAdmin() || ($user->role === Role::REPORTER && $article->author_id === $user->id);
    }

    public function schedule(User $user, Article $article): bool
    {
        return $this->publish($user, $article);
    }

    public function revert(User $user, Article $article): bool
    {
        return $user->isAdmin();
    }

    public function breaking(User $user, Article $article): bool
    {
        return $user->isAdmin();
    }
}
