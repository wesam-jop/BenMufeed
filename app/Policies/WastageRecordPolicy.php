<?php

namespace App\Policies;

use App\Models\WastageRecord;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class WastageRecordPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'wastage';
    }
}
