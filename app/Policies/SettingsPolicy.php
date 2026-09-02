<?php

namespace App\Policies;

use App\Models\User;

class SettingsPolicy
{
    public function view(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->can('settings.view');
    }

    public function update(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->can('settings.update');
    }
}
