<?php

namespace App\Policies;

use App\Models\RestaurantTable;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class RestaurantTablePolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'restaurant-tables';
    }
}
