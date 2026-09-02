<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class AuditLogPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'audit';
    }
}
