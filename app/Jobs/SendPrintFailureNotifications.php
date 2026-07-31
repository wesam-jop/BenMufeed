<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\PrintJob;
use App\Models\User;
use App\Notifications\PrintJobFailedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPrintFailureNotifications implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $printJobId,
    ) {}

    public function handle(): void
    {
        $job = PrintJob::query()->find($this->printJobId);

        if ($job === null) {
            return;
        }

        User::query()
            ->where(function ($query): void {
                $query->where('role', UserRole::Admin->value)
                    ->orWhere('is_admin', true);
            })
            ->get()
            ->each(fn (User $admin) => $admin->notify(new PrintJobFailedNotification($job)));
    }
}
