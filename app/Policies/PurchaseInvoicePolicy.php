<?php

namespace App\Policies;

use App\Models\PurchaseInvoice;
use App\Models\User;

class PurchaseInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, PurchaseInvoice $purchaseInvoice): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, PurchaseInvoice $purchaseInvoice): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, PurchaseInvoice $purchaseInvoice): bool
    {
        return $user->is_admin;
    }
}
