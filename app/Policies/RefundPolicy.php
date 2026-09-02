<?php

namespace App\Policies;

use App\Models\Refund;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class RefundPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'refunds';
    }
}
