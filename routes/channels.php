<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('kitchen.{branchId}', function (User $user, int $branchId) {
    return $user->can('kitchen.view') && ($user->is_super_admin || $user->branch_id === $branchId);
});

Broadcast::channel('pos.{branchId}', function (User $user, int $branchId) {
    return $user->can('pos.access') && ($user->is_super_admin || $user->branch_id === $branchId);
});

Broadcast::channel('customer.{customerId}', function ($user, int $customerId) {
    return (int) $user->id === $customerId;
});
