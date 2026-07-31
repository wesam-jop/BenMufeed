<?php

namespace App\Livewire\Pos;

use App\Livewire\Pos\Concerns\InteractsWithWebsiteOrders;
use App\Models\Order;
use App\Models\PosSetting;
use App\Services\WebsiteOrderWorkflowService;
use Livewire\Component;

class PosWebsiteOrderAlerts extends Component
{
    use InteractsWithWebsiteOrders;

    public int $lastNotifiedOrderId = 0;

    /** @var array<int, array<string, mixed>> */
    public array $toasts = [];

    public function mount(WebsiteOrderWorkflowService $workflow): void
    {
        $latest = $workflow->pendingWebsiteOrdersQuery()->max('id');

        if (is_numeric($latest)) {
            $this->lastNotifiedOrderId = (int) $latest;
        }
    }

    public function pollPending(WebsiteOrderWorkflowService $workflow): void
    {
        $previousMax = $this->lastNotifiedOrderId;
        $settings = PosSetting::current();

        $orders = $workflow->pendingWebsiteOrders();
        $currency = config('store.currency_symbol', '$');

        $this->pending = $orders
            ->map(fn (Order $order): array => $this->formatPendingOrder($order, $currency))
            ->values()
            ->all();

        foreach ($this->pending as $row) {
            $orderId = (int) $row['id'];

            if ($orderId <= $previousMax) {
                continue;
            }

            $this->lastNotifiedOrderId = max($this->lastNotifiedOrderId, $orderId);

            $this->toasts[] = [
                'id' => $orderId,
                'title' => $row['is_delivery'] ? 'طلب توصيل جديد' : 'طلب موقع جديد',
                'sub' => $row['summary'],
                'url' => route('pos.website-orders', ['order' => $orderId]),
            ];

            if ($settings->kitchen_alert_sound) {
                $kind = $row['is_delivery'] ? 'website-delivery' : 'website';
                $this->dispatch('pos-play-alert', kind: $kind);
            }

            $this->dispatch(
                'pos-browser-notify',
                title: $row['is_delivery'] ? 'توصيل — طلب جديد' : 'طلب موقع جديد',
                body: $row['summary'],
                tag: 'website-order-'.$orderId,
                url: route('pos.website-orders', ['order' => $orderId]),
            );
        }

        if (count($this->toasts) > 5) {
            $this->toasts = array_slice($this->toasts, -5);
        }
    }

    public function dismissToast(int $orderId): void
    {
        $this->toasts = array_values(array_filter(
            $this->toasts,
            fn (array $row): bool => (int) $row['id'] !== $orderId,
        ));
    }

    protected function afterOrderAccepted(int $orderId): void
    {
        $this->dismissToast($orderId);
    }

    protected function afterOrderRejected(int $orderId): void
    {
        $this->dismissToast($orderId);
    }

    public function render()
    {
        return view('livewire.pos.website-order-alerts');
    }
}
