<?php

namespace App\Policies;

use App\Models\ChartOfAccount;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccountPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'chart-of-accounts';
    }
}
