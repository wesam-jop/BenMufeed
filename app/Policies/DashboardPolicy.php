<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy
{
    public function view(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->can('dashboard.view');
    }
}
