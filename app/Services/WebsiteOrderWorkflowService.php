<?php

namespace App\Services;

use App\Data\WebsiteOrderAcceptance;
use App\Events\RestaurantMetricsChanged;
use App\Enums\KitchenTicketKind;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PosServiceType;
use App\Jobs\SendCustomerDeliveryOrderAcceptedWhatsApp;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WebsiteOrderWorkflowService
{
    public function __construct(
        protected KitchenService $kitchen,
        protected ProductRecipeService $recipes,
    ) {}

    public function accept(Order $order, ?User $reviewer = null): WebsiteOrderAcceptance
    {
        [$accepted, $ticket] = DB::transaction(function () use ($order, $reviewer): array {
            $order = Order::query()->lockForUpdate()->with('items')->findOrFail($order->getKey());

            $this->assertCanReview($order);

            $kind = match ($order->service_type) {
                PosServiceType::Delivery => KitchenTicketKind::DeliveryWebsite,
                default => KitchenTicketKind::TakeawayWebsite,
            };

            $this->recipes->consumeForOrder($order);

            $ticket = $this->kitchen->recordBatch(
                $order,
                $order->items->pluck('id')->all(),
                $kind,
            );

            $order->forceFill([
                'status' => OrderStatus::Reviewed,
                'reviewed_at' => now(),
                'cashier_id' => $reviewer?->getKey() ?? $order->cashier_id,
            ])->save();

            return [$order->fresh(['items']), $ticket];
        });

        RestaurantMetricsChanged::dispatch(now()->toDateString());

        if ($accepted->service_type === PosServiceType::Delivery) {
            SendCustomerDeliveryOrderAcceptedWhatsApp::dispatch($accepted->getKey());
        }

        return new WebsiteOrderAcceptance($accepted, $ticket);
    }

    public function reject(Order $order, ?string $reason = null): Order
    {
        $rejected = DB::transaction(function () use ($order, $reason): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            $this->assertCanReview($order);

            $note = filled($reason) ? trim($reason) : null;
            $existingNote = (string) ($order->customer_note ?? '');
            $mergedNote = $note !== null
                ? trim($existingNote."\n[ملغى]: ".$note)
                : $existingNote;

            $order->forceFill([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'customer_note' => $mergedNote !== '' ? $mergedNote : null,
            ])->save();

            return $order->fresh();
        });

        RestaurantMetricsChanged::dispatch(now()->toDateString());

        return $rejected;
    }

    public function pendingWebsiteOrdersCount(): int
    {
        return $this->pendingWebsiteOrdersQuery()->count();
    }

    /**
     * @return Collection<int, Order>
     */
    public function pendingWebsiteOrders(): Collection
    {
        return $this->pendingWebsiteOrdersQuery()
            ->withCount('items')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Order>
     */
    public function pendingWebsiteOrdersQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Order::query()
            ->where('source', OrderSource::Website)
            ->where('status', OrderStatus::New)
            ->whereIn('service_type', [PosServiceType::Takeaway, PosServiceType::Delivery]);
    }

    protected function assertCanReview(Order $order): void
    {
        if ($order->source !== OrderSource::Website) {
            throw ValidationException::withMessages([
                'order' => 'هذا الطلب ليس من الموقع.',
            ]);
        }

        if ($order->service_type === PosServiceType::Table) {
            throw ValidationException::withMessages([
                'order' => 'طلبات الطاولة تُدار من نقطة البيع.',
            ]);
        }

        if ($order->status !== OrderStatus::New) {
            throw ValidationException::withMessages([
                'order' => 'تمت معالجة هذا الطلب مسبقاً.',
            ]);
        }
    }
}
