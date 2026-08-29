<?php

namespace App\Policies;

use App\Models\User;

class MediaPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isReporter();
    }
}
