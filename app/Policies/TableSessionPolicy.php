<?php

namespace App\Policies;

use App\Models\TableSession;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class TableSessionPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'table-sessions';
    }
}
