<?php

namespace App\Policies;

use App\Models\User;

class Policy
{
    public function replicate(User $user): bool
    {
        return false;
    }
}
