<?php

namespace App\Policies;

use App\Models\Product;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class ProductPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'products';
    }
}
