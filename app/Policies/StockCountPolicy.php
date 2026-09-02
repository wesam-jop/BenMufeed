<?php

namespace App\Policies;

use App\Models\StockCount;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class StockCountPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'stock-counts';
    }
}
