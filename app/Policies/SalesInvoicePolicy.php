<?php

namespace App\Policies;

use App\Models\SalesInvoice;
use App\Models\User;

class SalesInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, SalesInvoice $salesInvoice): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, SalesInvoice $salesInvoice): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, SalesInvoice $salesInvoice): bool
    {
        return $user->is_admin;
    }
}
