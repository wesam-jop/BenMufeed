<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class RecipePolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'recipes';
    }
}
