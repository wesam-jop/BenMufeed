<?php

namespace App\Policies;

use App\Models\ProductionOrder;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class ProductionOrderPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'production-orders';
    }
}
