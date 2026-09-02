<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class SupplierPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'suppliers';
    }
}
