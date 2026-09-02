<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class InvoicePolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'invoices';
    }
}
