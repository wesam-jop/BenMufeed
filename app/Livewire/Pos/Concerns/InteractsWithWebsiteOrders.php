<?php

namespace App\Livewire\Pos\Concerns;

use App\Data\WebsiteOrderAcceptance;
use App\Enums\PosServiceType;
use App\Models\Order;
use App\Services\WebsiteOrderWorkflowService;
use App\Support\DispatchesPrintJobs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

trait InteractsWithWebsiteOrders
{
    use DispatchesPrintJobs;

    /** @var array<int, array<string, mixed>> */
    public array $pending = [];

    public ?int $rejectingOrderId = null;

    public string $rejectReason = '';

    public ?string $flashMessage = null;

    public function accept(int $orderId, WebsiteOrderWorkflowService $workflow): void
    {
        $this->resetErrorBag();
        $this->flashMessage = null;

        try {
            $order = Order::query()->findOrFail($orderId);
            $acceptance = $workflow->accept($order, Auth::user());
            $this->dispatchFulfillmentAfterAccept($acceptance);
            $this->flashMessage = "تم قبول الطلب #{$orderId} — طُبع إيصال الكاشير وتذكرة المطبخ.";
            $this->rejectingOrderId = null;
            $this->refreshPending($workflow);
            $this->afterOrderAccepted($orderId);
        } catch (ValidationException $exception) {
            $this->addError('workflow', collect($exception->errors())->flatten()->first() ?? 'تعذّر قبول الطلب.');
        }
    }

    public function startReject(int $orderId): void
    {
        $this->rejectingOrderId = $orderId;
        $this->rejectReason = '';
        $this->resetErrorBag();
    }

    public function cancelReject(): void
    {
        $this->rejectingOrderId = null;
        $this->rejectReason = '';
    }

    public function reject(WebsiteOrderWorkflowService $workflow): void
    {
        if ($this->rejectingOrderId === null) {
            return;
        }

        $this->resetErrorBag();
        $this->flashMessage = null;
        $orderId = $this->rejectingOrderId;

        try {
            $order = Order::query()->findOrFail($orderId);
            $workflow->reject($order, $this->rejectReason !== '' ? $this->rejectReason : null);
            $this->flashMessage = "تم رفض الطلب #{$orderId}.";
            $this->rejectingOrderId = null;
            $this->rejectReason = '';
            $this->refreshPending($workflow);
            $this->afterOrderRejected($orderId);
        } catch (ValidationException $exception) {
            $this->addError('workflow', collect($exception->errors())->flatten()->first() ?? 'تعذّر رفض الطلب.');
        }
    }

    protected function afterOrderAccepted(int $orderId): void
    {
        //
    }

    protected function afterOrderRejected(int $orderId): void
    {
        //
    }

    protected function dispatchFulfillmentAfterAccept(WebsiteOrderAcceptance $acceptance): void
    {
        $orderId = $acceptance->order->getKey();

        $this->dispatchCashierPrint($orderId, 'website_accept');

        if ($acceptance->kitchenTicket !== null) {
            $this->dispatchKitchenPrint($acceptance->kitchenTicket->getKey(), 'website_accept');
        }

        $this->dispatch('website-order-accepted');
    }

    protected function refreshPending(WebsiteOrderWorkflowService $workflow): void
    {
        $currency = config('store.currency_symbol', '$');

        $this->pending = $workflow->pendingWebsiteOrders()
            ->map(fn (Order $order): array => $this->formatPendingOrder($order, $currency))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatPendingOrder(Order $order, string $currency): array
    {
        return [
            'id' => $order->getKey(),
            'service' => $order->service_type?->label() ?? '—',
            'is_delivery' => $order->service_type === PosServiceType::Delivery,
            'customer' => $order->customer_name ?: '—',
            'phone' => $order->customer_phone ?: '—',
            'total' => number_format((float) $order->total, 2),
            'items_count' => (int) $order->items_count,
            'note' => $order->customer_note,
            'map_url' => $order->deliveryMapUrl(),
            'location_note' => $order->delivery_location_note,
            'created_at' => $order->created_at?->format('Y-m-d H:i') ?? '—',
            'summary' => sprintf(
                '#%d — %s — %s — %s %s',
                $order->getKey(),
                $order->service_type?->label() ?? 'طلب',
                $order->customer_name ?: 'زبون',
                number_format((float) $order->total, 2),
                $currency,
            ),
        ];
    }
}
