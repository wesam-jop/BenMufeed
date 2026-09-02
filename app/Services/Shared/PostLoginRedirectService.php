<?php

namespace App\Services\Shared;

use App\Models\User;

class PostLoginRedirectService
{
    public function forUser(User $user): string
    {
        if ($user->is_super_admin || $user->can('dashboard.view')) {
            return route('admin.dashboard');
        }

        if ($user->can('pos.access')) {
            return route('pos.terminal');
        }

        if ($user->can('kitchen.view')) {
            return route('kitchen.display');
        }

        return route('admin.dashboard');
    }
}
