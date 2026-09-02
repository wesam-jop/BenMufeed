<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Database\Eloquent\Model;

class JournalEntryPolicy
{
    use ChecksPermissions;

    protected function permissionPrefix(): string
    {
        return 'journal-entries';
    }
}
