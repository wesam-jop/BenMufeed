<?php

namespace App\Livewire\Pos;

use App\Livewire\Pos\Concerns\InteractsWithWebsiteOrders;
use App\Enums\PosServiceType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\WebsiteOrderWorkflowService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.pos')]
#[Title('طلبات الموقع — بن مفيد')]
class PosWebsiteOrders extends Component
{
    use InteractsWithWebsiteOrders;

    /** @var array<string, mixed>|null */
    public ?array $detailOrder = null;

    public function mount(WebsiteOrderWorkflowService $workflow): void
    {
        $this->refreshPending($workflow);

        $orderId = request()->integer('order');

        if ($orderId > 0) {
            $this->showDetail($orderId, $workflow);
        }
    }

    public function pollPending(WebsiteOrderWorkflowService $workflow): void
    {
        $this->refreshPending($workflow);

        if ($this->detailOrder !== null) {
            $detailId = (int) ($this->detailOrder['id'] ?? 0);
            $stillPending = collect($this->pending)->contains(fn (array $row): bool => (int) $row['id'] === $detailId);

            if (! $stillPending) {
                $this->closeDetail();
            }
        }
    }

    public function showDetail(int $orderId, WebsiteOrderWorkflowService $workflow): void
    {
        $order = $workflow->pendingWebsiteOrdersQuery()
            ->with(['items.addons'])
            ->find($orderId);

        if ($order === null) {
            $this->detailOrder = null;

            return;
        }

        $this->detailOrder = $this->formatOrderDetail($order);
        $this->rejectingOrderId = null;
        $this->rejectReason = '';
    }

    public function closeDetail(): void
    {
        $this->detailOrder = null;
        $this->cancelReject();
    }

    protected function afterOrderAccepted(int $orderId): void
    {
        $this->closeDetail();
    }

    protected function afterOrderRejected(int $orderId): void
    {
        $this->closeDetail();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatOrderDetail(Order $order): array
    {
        $currency = config('store.currency_symbol', '$');
        $deliveryFee = (float) ($order->delivery_fee ?? 0);
        $total = (float) $order->total;
        $subtotal = max(0, round($total - $deliveryFee, 2));

        return [
            'id' => $order->getKey(),
            'service' => $order->service_type?->label() ?? '—',
            'is_delivery' => $order->service_type === PosServiceType::Delivery,
            'customer' => $order->customer_name ?: '—',
            'phone' => $order->customer_phone ?: '—',
            'note' => $order->customer_note,
            'location_note' => $order->delivery_location_note,
            'map_url' => $order->deliveryMapUrl(),
            'created_at' => $order->created_at?->format('Y-m-d H:i') ?? '—',
            'subtotal' => number_format($subtotal, 2),
            'delivery_fee' => number_format($deliveryFee, 2),
            'total' => number_format($total, 2),
            'currency' => $currency,
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'label' => $item->displayLabel(),
                'quantity' => (int) $item->quantity,
                'unit_price' => number_format((float) $item->unit_price, 2),
                'line_total' => number_format((float) $item->line_total, 2),
                'addons' => $item->addons->map(fn ($addon): string => $addon->addon_name.($addon->quantity > 1 ? ' ×'.$addon->quantity : ''))->all(),
            ])->all(),
        ];
    }

    public function render()
    {
        return view('livewire.pos.website-orders', [
            'openTablesCount' => app(\App\Services\PosService::class)->occupiedTableNumbers(),
        ]);
    }
}

