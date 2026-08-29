<?php

namespace App\Policies;

use App\Models\User;

class AdvertisementPolicy
{
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
