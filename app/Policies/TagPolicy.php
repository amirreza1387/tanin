<?php

namespace App\Policies;

use App\Models\User;

class TagPolicy
{
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
