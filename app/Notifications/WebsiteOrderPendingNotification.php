<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WebsiteOrderPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $service = $this->order->service_type?->label() ?? 'طلب';
        $isDelivery = $this->order->service_type?->requiresDeliveryAddress() ?? false;

        return [
            'title' => $isDelivery ? 'طلب توصيل — بانتظار القبول' : 'طلب موقع بانتظار القبول',
            'body' => "#{$this->order->getKey()} — {$service} — ".number_format((float) $this->order->total, 2),
            'format' => 'filament',
            'icon' => 'heroicon-o-globe-alt',
            'iconColor' => 'warning',
            'status' => 'warning',
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'عرض الطلب',
                    'url' => url('/admin/orders/'.$this->order->getKey()),
                    'color' => 'warning',
                ],
            ],
        ];
    }
}
