<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class OrderPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'orders';
    }

    public function acceptDelivery(User $user, Order $order): bool
    {
        return $this->allows($user, 'accept_delivery');
    }

    public function rejectDelivery(User $user, Order $order): bool
    {
        return $this->allows($user, 'reject_delivery');
    }

    public function acceptTable(User $user, Order $order): bool
    {
        return $this->allows($user, 'accept_table');
    }

    public function rejectTable(User $user, Order $order): bool
    {
        return $this->allows($user, 'reject_table');
    }
}
