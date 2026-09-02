<?php

namespace App\Policies;

use App\Models\CashRegisterSession;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class CashRegisterSessionPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'cash-register-sessions';
    }
}
