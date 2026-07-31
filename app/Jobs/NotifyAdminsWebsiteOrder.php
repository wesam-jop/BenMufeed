<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Notifications\WebsiteOrderPendingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyAdminsWebsiteOrder implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
    ) {}

    public function handle(): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order === null || $order->status !== OrderStatus::New) {
            return;
        }

        User::query()
            ->where(function ($query): void {
                $query->where('role', UserRole::Admin->value)
                    ->orWhere('role', UserRole::Cashier->value)
                    ->orWhere('is_admin', true);
            })
            ->get()
            ->each(fn (User $user) => $user->notify(new WebsiteOrderPendingNotification($order)));
    }
}
