<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->can('reports.view');
    }

    public function sales(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function products(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function inventory(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function customers(User $user): bool
    {
        return $this->viewAny($user);
    }
}
