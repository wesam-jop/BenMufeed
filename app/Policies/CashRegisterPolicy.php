<?php

namespace App\Policies;

use App\Models\CashRegister;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class CashRegisterPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'cash-registers';
    }
}
