<?php

namespace App\Policies;

use App\Models\SiteSetting;
use App\Models\User;

class SiteSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, SiteSetting $siteSetting): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SiteSetting $siteSetting): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, SiteSetting $siteSetting): bool
    {
        return false;
    }
}
