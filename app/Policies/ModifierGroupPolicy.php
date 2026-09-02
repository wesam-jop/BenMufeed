<?php

namespace App\Policies;

use App\Models\ModifierGroup;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class ModifierGroupPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'modifier-groups';
    }
}
