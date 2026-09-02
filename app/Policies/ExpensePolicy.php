<?php

namespace App\Policies;

use App\Models\Expense;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class ExpensePolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'expenses';
    }
}
