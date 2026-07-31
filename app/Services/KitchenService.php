<?php

namespace App\Services;

use App\Enums\KitchenTicketKind;
use App\Enums\KitchenTicketStatus;
use App\Enums\OrderSource;
use App\Enums\PosServiceType;
use App\Models\KitchenTicket;
use App\Models\Order;
use App\Models\PosSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KitchenService
{
    /**
     * @param  array<int, int>  $orderItemIds
     */
    public function recordBatch(Order $order, array $orderItemIds, KitchenTicketKind $kind): ?KitchenTicket
    {
        $orderItemIds = array_values(array_unique(array_filter($orderItemIds)));

        if ($orderItemIds === []) {
            return null;
        }

        $order->loadMissing('items');

        $ticket = KitchenTicket::query()->create([
            'order_id' => $order->getKey(),
            'batch_token' => (string) Str::uuid(),
            'kind' => $kind,
            'status' => KitchenTicketStatus::Pending,
            'table_number' => $order->table_number,
            'service_label' => $this->serviceLabel($order),
            'source' => $order->source,
        ]);

        $ticket->orderItems()->sync($orderItemIds);

        return $ticket->load(['orderItems', 'order']);
    }

    public function markReady(KitchenTicket $ticket): KitchenTicket
    {
        $ticket->forceFill([
            'status' => KitchenTicketStatus::Ready,
            'ready_at' => now(),
        ])->save();

        return $ticket->fresh(['orderItems', 'order']);
    }

    public function markPrinted(KitchenTicket $ticket): void
    {
        if ($ticket->printed_at !== null) {
            return;
        }

        $ticket->forceFill(['printed_at' => now()])->save();
    }

    /**
     * @return Collection<int, KitchenTicket>
     */
    public function pendingTickets(): Collection
    {
        return KitchenTicket::query()
            ->with([
                'order:id,customer_note,customer_name,customer_phone,delivery_location_note,service_type',
                'orderItems:id,order_id,product_name,variant_name,options_label,quantity',
            ])
            ->where('status', KitchenTicketStatus::Pending)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @return Collection<int, KitchenTicket>
     */
    public function ticketsSince(int $lastId): Collection
    {
        return KitchenTicket::query()
            ->withCount('orderItems')
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, KitchenTicket>
     */
    public function unprintedKitchenTickets(): Collection
    {
        return KitchenTicket::query()
            ->whereNull('printed_at')
            ->orderBy('id')
            ->limit(10)
            ->get(['id']);
    }

    public function settings(): PosSetting
    {
        return PosSetting::current();
    }

    protected function serviceLabel(Order $order): string
    {
        if ($order->service_type === PosServiceType::Table && $order->table_number !== null) {
            return 'طاولة '.$order->table_number;
        }

        if ($order->service_type === PosServiceType::Delivery) {
            return 'توصيل — موقع';
        }

        if ($order->source === OrderSource::Website) {
            return 'استلام — موقع';
        }

        return 'سفري';
    }
}
