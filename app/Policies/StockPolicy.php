<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'view');
    }

    public function adjust(User $user): bool
    {
        return $this->allows($user, 'adjust');
    }

    protected function allows(User $user, string $action): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $user->can("inventory.{$action}");
    }
}
