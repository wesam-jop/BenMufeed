<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class UserPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'users';
    }
}
