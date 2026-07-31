<?php

namespace App\Services;

use App\Events\OrderCompleted;
use App\Events\RestaurantMetricsChanged;
use App\Enums\KitchenTicketKind;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PosServiceType;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PosService
{
    public function __construct(
        protected OrderRepositoryInterface $orders,
        protected ProductRecipeService $recipes,
        protected KitchenService $kitchen,
        protected ProductOptionsService $options,
    ) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     */
    public function placeTakeaway(
        array $lines,
        User $cashier,
        ?string $customerName = null,
        ?string $customerNote = null,
    ): int {
        [$products, $items, $total] = $this->prepareLines($lines);

        $orderId = DB::transaction(function () use ($cashier, $customerName, $customerNote, $items, $total): int {
            $now = now();

            $order = $this->orders->createWithItems(
                [
                    'status' => OrderStatus::Completed,
                    'source' => OrderSource::Pos,
                    'service_type' => PosServiceType::Takeaway,
                    'table_number' => null,
                    'cashier_id' => $cashier->getKey(),
                    'total' => $total,
                    'paid_amount' => $total,
                    'closed_at' => $now,
                    'customer_name' => filled($customerName) ? $customerName : 'عميل سفري',
                    'customer_phone' => null,
                    'customer_note' => $customerNote,
                ],
                $items,
            );

            $this->recipes->consumeForOrder($order);

            $this->kitchen->recordBatch(
                $order,
                $order->items->pluck('id')->all(),
                KitchenTicketKind::TakeawayPos,
            );

            return $order->getKey();
        });

        OrderCompleted::dispatch($orderId);

        return $orderId;
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     */
    public function sendToTable(
        array $lines,
        User $cashier,
        int $tableNumber,
        ?string $customerNote = null,
    ): int {
        $this->assertValidTableNumber($tableNumber);

        [$products, $items, $additionTotal] = $this->prepareLines($lines);

        return DB::transaction(function () use ($cashier, $tableNumber, $customerNote, $items, $additionTotal, $lines): int {
            $order = $this->openTableOrderQuery()
                ->where('table_number', $tableNumber)
                ->lockForUpdate()
                ->first();

            if ($order !== null) {
                $newItemIds = [];

                foreach ($items as $line) {
                    $newItemIds[] = $this->createOrderItem($order, $line)->getKey();
                }

                $order->forceFill([
                    'total' => round((float) $order->total + $additionTotal, 2),
                    'customer_note' => filled($customerNote) ? $customerNote : $order->customer_note,
                    'restaurant_table_id' => $order->restaurant_table_id ?? RestaurantTable::query()->where('number', $tableNumber)->value('id'),
                ])->save();

                $this->recipes->consumeForLines($lines);

                $this->kitchen->recordBatch(
                    $order->fresh(['items']),
                    $newItemIds,
                    KitchenTicketKind::TableAppend,
                );

                return $order->getKey();
            }

            $restaurantTable = RestaurantTable::query()->where('number', $tableNumber)->first();

            $order = $this->orders->createWithItems(
                [
                    'status' => OrderStatus::Open,
                    'source' => OrderSource::Pos,
                    'service_type' => PosServiceType::Table,
                    'table_number' => $tableNumber,
                    'restaurant_table_id' => $restaurantTable?->getKey(),
                    'cashier_id' => $cashier->getKey(),
                    'total' => $additionTotal,
                    'paid_amount' => null,
                    'closed_at' => null,
                    'customer_name' => $restaurantTable?->displayName() ?? 'طاولة '.$tableNumber,
                    'customer_phone' => null,
                    'customer_note' => $customerNote,
                ],
                $items,
            );

            $this->recipes->consumeForOrder($order);

            $this->kitchen->recordBatch(
                $order,
                $order->items->pluck('id')->all(),
                KitchenTicketKind::NewTable,
            );

            return $order->getKey();
        });
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     * @return array{order_id: int, customer_session_token: string}
     */
    public function receiveWebsiteTableOrder(
        array $lines,
        RestaurantTable $table,
        ?string $customerNote = null,
        ?string $customerSessionToken = null,
        ?int $storefrontCustomerId = null,
    ): array {
        $this->assertValidTableNumber($table->number);

        [$products, $items, $additionTotal] = $this->prepareLines($lines);

        return DB::transaction(function () use ($table, $customerNote, $items, $additionTotal, $lines, $customerSessionToken, $storefrontCustomerId): array {
            $existing = $this->openTableOrderQuery()
                ->where('table_number', $table->number)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if (
                    $existing->customer_session_token === null
                    || ! filled($customerSessionToken)
                    || ! hash_equals((string) $existing->customer_session_token, (string) $customerSessionToken)
                ) {
                    throw ValidationException::withMessages([
                        'table_token' => 'الطاولة مفتوحة — لا يمكن إضافة طلب جديد عليها.',
                    ]);
                }

                $newItemIds = [];

                foreach ($items as $line) {
                    $newItemIds[] = $this->createOrderItem($existing, $line)->getKey();
                }

                $existing->forceFill([
                    'total' => round((float) $existing->total + $additionTotal, 2),
                    'customer_note' => filled($customerNote) ? $customerNote : $existing->customer_note,
                    'storefront_customer_id' => $storefrontCustomerId ?? $existing->storefront_customer_id,
                ])->save();

                $this->recipes->consumeForLines($lines);

                $this->kitchen->recordBatch(
                    $existing->fresh(['items']),
                    $newItemIds,
                    KitchenTicketKind::TableAppend,
                );

                return [
                    'order_id' => $existing->getKey(),
                    'customer_session_token' => (string) $existing->customer_session_token,
                ];
            }

            $sessionToken = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(48));

            $order = $this->orders->createWithItems(
                [
                    'status' => OrderStatus::Open,
                    'source' => OrderSource::Website,
                    'service_type' => PosServiceType::Table,
                    'table_number' => $table->number,
                    'restaurant_table_id' => $table->getKey(),
                    'customer_session_token' => $sessionToken,
                    'storefront_customer_id' => $storefrontCustomerId,
                    'cashier_id' => null,
                    'total' => $additionTotal,
                    'paid_amount' => null,
                    'closed_at' => null,
                    'customer_name' => $table->displayName(),
                    'customer_phone' => null,
                    'customer_note' => $customerNote,
                ],
                $items,
            );

            $this->recipes->consumeForOrder($order);

            $this->kitchen->recordBatch(
                $order,
                $order->items->pluck('id')->all(),
                KitchenTicketKind::NewTable,
            );

            return [
                'order_id' => $order->getKey(),
                'customer_session_token' => $sessionToken,
            ];
        });
    }

    public function closeTable(int $tableNumber, float $paidAmount, User $cashier): Order
    {
        $this->assertValidTableNumber($tableNumber);

        $paidAmount = round($paidAmount, 2);

        if ($paidAmount < 0) {
            throw ValidationException::withMessages([
                'paidAmount' => 'المبلغ المقبوض لا يمكن أن يكون سالباً.',
            ]);
        }

        $order = DB::transaction(function () use ($tableNumber, $paidAmount, $cashier): Order {
            $order = $this->openTableOrderQuery()
                ->where('table_number', $tableNumber)
                ->lockForUpdate()
                ->first();

            if ($order === null) {
                throw ValidationException::withMessages([
                    'table' => "الطاولة {$tableNumber} غير مفتوحة.",
                ]);
            }

            $order->forceFill([
                'status' => OrderStatus::Completed,
                'paid_amount' => $paidAmount,
                'closed_at' => now(),
                'cashier_id' => $cashier->getKey(),
            ])->save();

            return $order->fresh(['items']);
        });

        OrderCompleted::dispatch(
            $order->getKey(),
            $order->closed_at?->format('Y-m-d') ?? now()->toDateString(),
        );

        if ($order->restaurant_table_id !== null) {
            app(\App\Services\TableScanSessionService::class)->revokeForTable((int) $order->restaurant_table_id);
        }

        return $order;
    }

    public function findOpenTableOrder(int $tableNumber): ?Order
    {
        $this->assertValidTableNumber($tableNumber);

        return $this->openTableOrderQuery()
            ->with('items')
            ->where('table_number', $tableNumber)
            ->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Order>
     */
    protected function openTableOrderQuery()
    {
        return Order::query()
            ->where('service_type', PosServiceType::Table)
            ->where('status', OrderStatus::Open);
    }

    public function openTablesCount(): int
    {
        return $this->openTableOrderQuery()->count();
    }

    /**
     * @return Collection<int, Order>
     */
    public function openTables(): Collection
    {
        return $this->openTableOrderQuery()
            ->withCount('items')
            ->orderBy('table_number')
            ->get();
    }

    /**
     * @return array<int, int>
     */
    public function occupiedTableNumbers(): array
    {
        return $this->openTableOrderQuery()
            ->pluck('table_number')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{0: Collection<int, Product>, 1: Collection<int, array<string, mixed>>, 2: float}
     */
    protected function prepareLines(array $lines): array
    {
        $lines = $this->normalizeLines($lines);

        [$products, $resolved, $total] = $this->options->prepareOrderLines($lines);

        $this->recipes->assertStockAvailable($this->recipeLinesFrom($lines), $products);

        $items = $resolved->map(fn (array $line): array => [
            'product_id' => $line['product_id'],
            'product_variant_id' => $line['product_variant_id'],
            'product_name' => $line['product_name'],
            'variant_name' => $line['variant_name'],
            'options_label' => $line['options_label'],
            'quantity' => $line['quantity'],
            'unit_price' => $line['unit_price'],
            'unit_cost' => $line['unit_cost'],
            'line_total' => $line['line_total'],
            'addons' => collect($line['addons'])->map(fn (array $addon): array => [
                'product_addon_id' => $addon['product_addon_id'],
                'addon_name' => $addon['addon_name'],
                'quantity' => $addon['quantity'],
                'unit_price' => $addon['unit_price'],
                'unit_cost' => $addon['unit_cost'],
                'line_total' => $addon['line_total'],
            ])->all(),
        ]);

        return [$products, $items, $total];
    }

    /**
     * @param  array<string, mixed>  $line
     */
    protected function createOrderItem(Order $order, array $line): \App\Models\OrderItem
    {
        $addons = $line['addons'] ?? [];
        unset($line['addons']);

        $orderItem = $order->items()->create($line);

        foreach ($addons as $addonRow) {
            $orderItem->addons()->create($addonRow);
        }

        return $orderItem;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array{product_id: int, quantity: int, product_variant_id?: int}>
     */
    protected function recipeLinesFrom(array $lines): array
    {
        return collect($lines)->map(fn (array $line): array => array_filter([
            'product_id' => (int) $line['product_id'],
            'product_variant_id' => isset($line['product_variant_id']) ? (int) $line['product_variant_id'] : null,
            'quantity' => max(1, (int) ($line['quantity'] ?? 1)),
        ], fn ($value) => $value !== null))->values()->all();
    }

    protected function normalizeLines(array $lines): array
    {
        return collect($lines)->map(function (array $line): array {
            $normalized = [
                'product_id' => (int) $line['product_id'],
                'quantity' => max(1, (int) ($line['quantity'] ?? 1)),
            ];

            if (array_key_exists('product_variant_id', $line) && $line['product_variant_id'] !== null && $line['product_variant_id'] !== '') {
                $normalized['product_variant_id'] = (int) $line['product_variant_id'];
            }

            if (! empty($line['addons']) && is_array($line['addons'])) {
                $normalized['addons'] = collect($line['addons'])->map(fn (array $addon): array => [
                    'product_addon_id' => (int) $addon['product_addon_id'],
                    'quantity' => max(1, (int) ($addon['quantity'] ?? 1)),
                ])->values()->all();
            }

            return $normalized;
        })->values()->all();
    }

    protected function assertValidTableNumber(int $tableNumber): void
    {
        $exists = RestaurantTable::query()
            ->where('number', $tableNumber)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'tableNumber' => 'الطاولة غير موجودة أو غير مفعّلة — أضفها من لوحة التحكم.',
            ]);
        }
    }

    /**
     * @return Collection<int, Order>
     */
    public function recentOrders(?string $date = null): Collection
    {
        $query = Order::query()
            ->with(['items', 'cashier'])
            ->withCount('items')
            ->where('source', OrderSource::Pos)
            ->latest('id');

        if (filled($date)) {
            $query->whereDate('created_at', $date);
        }

        return $query->limit(80)->get();
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     * @param  array{customer_name?: ?string, customer_note?: ?string, paid_amount?: ?float}  $meta
     */
    public function updateOrder(int $orderId, array $lines, array $meta = []): Order
    {
        if ($lines === []) {
            throw ValidationException::withMessages([
                'edit' => 'أضف صنفاً واحداً على الأقل.',
            ]);
        }

        $order = DB::transaction(function () use ($orderId, $lines, $meta): Order {
            $order = $this->findOrderForUpdate($orderId, lock: true);

            $oldLines = $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
            ])->all();

            $this->recipes->restoreForLines($oldLines);

            [$products, $items, $total] = $this->prepareLines($lines);

            $order->items()->each(fn ($item) => $item->addons()->delete());
            $order->items()->delete();

            foreach ($items as $line) {
                $this->createOrderItem($order, $line);
            }

            $updates = [
                'total' => $total,
            ];

            if (array_key_exists('customer_name', $meta) && filled($meta['customer_name'])) {
                $updates['customer_name'] = $meta['customer_name'];
            }

            if (array_key_exists('customer_note', $meta)) {
                $updates['customer_note'] = filled($meta['customer_note']) ? $meta['customer_note'] : null;
            }

            if ($order->status === OrderStatus::Completed && array_key_exists('paid_amount', $meta) && $meta['paid_amount'] !== null) {
                $paid = round((float) $meta['paid_amount'], 2);

                if ($paid < 0) {
                    throw ValidationException::withMessages([
                        'edit' => 'المبلغ المقبوض لا يمكن أن يكون سالباً.',
                    ]);
                }

                $updates['paid_amount'] = $paid;
            } elseif ($order->status === OrderStatus::Completed && $order->service_type === PosServiceType::Takeaway) {
                $updates['paid_amount'] = $total;
            }

            $order->forceFill($updates)->save();

            $this->recipes->consumeForLines($lines);

            return $order->fresh(['items', 'cashier']);
        });

        RestaurantMetricsChanged::dispatch(
            $order->closed_at?->format('Y-m-d') ?? $order->created_at?->format('Y-m-d'),
        );

        return $order;
    }

    public function deleteOrder(int $orderId): void
    {
        $saleDate = null;

        DB::transaction(function () use ($orderId, &$saleDate): void {
            $order = $this->findPosOrder($orderId, lock: true);

            $saleDate = $order->closed_at?->format('Y-m-d') ?? $order->created_at?->format('Y-m-d');

            $this->recipes->restoreForOrder($order);
            $order->items()->delete();
            $order->delete();
        });

        RestaurantMetricsChanged::dispatch($saleDate);
    }

    protected function findOrderForUpdate(int $orderId, bool $lock = false): Order
    {
        $query = Order::query()->where(function ($inner): void {
            $inner->where('source', OrderSource::Pos)
                ->orWhere(function ($websiteTable): void {
                    $websiteTable->where('source', OrderSource::Website)
                        ->where('service_type', PosServiceType::Table)
                        ->where('status', OrderStatus::Open);
                });
        });

        if ($lock) {
            $query->lockForUpdate();
        }

        $order = $query->find($orderId);

        if ($order === null) {
            throw ValidationException::withMessages([
                'order' => 'الفاتورة غير موجودة.',
            ]);
        }

        return $order;
    }

    protected function findPosOrder(int $orderId, bool $lock = false): Order
    {
        $query = Order::query()->where('source', OrderSource::Pos);

        if ($lock) {
            $query->lockForUpdate();
        }

        $order = $query->find($orderId);

        if ($order === null) {
            throw ValidationException::withMessages([
                'order' => 'الفاتورة غير موجودة.',
            ]);
        }

        return $order;
    }

    /**
     * @deprecated Use placeTakeaway()
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     */
    public function placeSale(
        array $lines,
        User $cashier,
        ?string $customerName = null,
        ?string $customerNote = null,
    ): int {
        return $this->placeTakeaway($lines, $cashier, $customerName, $customerNote);
    }
}
