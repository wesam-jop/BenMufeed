<?php

namespace App\Policies;

use App\Models\Warehouse;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class WarehousePolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'warehouses';
    }
}
