<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class StockTransferPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'stock-transfers';
    }
}
