<?php

namespace App\Policies;

use App\Models\User;

class AccountingReportPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->can('accounting-reports.view');
    }

    public function profitLoss(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function balanceSheet(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function trialBalance(User $user): bool
    {
        return $this->viewAny($user);
    }
}
