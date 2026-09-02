<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'purchase-orders';
    }
}
