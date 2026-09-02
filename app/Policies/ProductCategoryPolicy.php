<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class ProductCategoryPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'product-categories';
    }
}
