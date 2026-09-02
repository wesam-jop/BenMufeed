<?php

namespace App\Policies;

use App\Models\PurchaseInvoice;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoicePolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'purchase-invoices';
    }
}
