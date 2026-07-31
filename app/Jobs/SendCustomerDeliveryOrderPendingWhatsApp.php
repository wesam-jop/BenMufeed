<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\PosServiceType;
use App\Models\Order;
use App\Services\DeliveryOrderWhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCustomerDeliveryOrderPendingWhatsApp implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
    ) {}

    public function handle(DeliveryOrderWhatsAppService $whatsapp): void
    {
        $order = Order::query()->find($this->orderId);

        if ($order === null
            || $order->service_type !== PosServiceType::Delivery
            || $order->status !== OrderStatus::New) {
            return;
        }

        $whatsapp->notifyCustomerPending($order);
    }
}
