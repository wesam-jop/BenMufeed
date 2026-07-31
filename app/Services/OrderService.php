<?php

namespace App\Services;

use App\Events\WebsiteOrderPlaced;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PosServiceType;
use App\Models\PosSetting;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Order;
use App\Models\StorefrontCustomer;
use App\Models\StorefrontCustomerAddress;
use App\Services\StorefrontCustomerAddressService;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        protected OrderRepositoryInterface $orders,
        protected WhatsAppCheckoutService $whatsapp,
        protected RestaurantTableService $tables,
        protected PosService $pos,
        protected KitchenService $kitchen,
        protected ProductOptionsService $options,
    ) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     * @param  array{
     *     service_type: string,
     *     customer_name?: ?string,
     *     customer_phone?: ?string,
     *     customer_note?: ?string,
     *     table_token?: ?string,
     *     table_session_token?: ?string,
     *     table_scan_session?: ?string,
     *     delivery_latitude?: ?float,
     *     delivery_longitude?: ?float,
     *     delivery_location_note?: ?string,
     *     storefront_customer_id?: ?int,
     *     saved_address_id?: ?int,
     *     save_address?: bool,
     *     address_label?: ?string,
     * }  $meta
     * @return array{order_id: int, total: float, service_type: string, table_number: ?int, table_qr_token: ?string, customer_session_token: ?string}
     */
    public function placeCustomerOrder(array $lines, array $meta): array
    {
        $serviceType = PosServiceType::from($meta['service_type']);

        if ($serviceType === PosServiceType::Delivery) {
            $this->assertDeliveryProductsAllowed($lines);
        }

        if ($serviceType === PosServiceType::Table) {
            $table = $this->tables->resolveToken((string) ($meta['table_token'] ?? ''));

            if (filled($meta['table_scan_session'] ?? null)) {
                app(\App\Services\TableScanSessionService::class)->validateAndTouch(
                    (string) $meta['table_scan_session'],
                    $table,
                );
            }

            $result = $this->pos->receiveWebsiteTableOrder(
                $lines,
                $table,
                filled($meta['customer_note'] ?? null) ? $meta['customer_note'] : null,
                filled($meta['table_session_token'] ?? null) ? (string) $meta['table_session_token'] : null,
                isset($meta['storefront_customer_id']) ? (int) $meta['storefront_customer_id'] : null,
            );

            $order = Order::query()->findOrFail($result['order_id']);

            return [
                'order_id' => $result['order_id'],
                'total' => (float) $order->total,
                'service_type' => $serviceType->value,
                'table_number' => $table->number,
                'table_label' => $table->displayName(),
                'table_qr_token' => $table->qr_token,
                'customer_session_token' => $result['customer_session_token'],
            ];
        }

        [$items, $subtotal] = $this->buildOrderItems($lines);

        $deliveryFee = $serviceType === PosServiceType::Delivery
            ? (float) PosSetting::current()->delivery_fee
            : 0.0;

        $total = round($subtotal + $deliveryFee, 2);

        $savedAddressId = null;
        $deliveryLatitude = $meta['delivery_latitude'] ?? null;
        $deliveryLongitude = $meta['delivery_longitude'] ?? null;
        $deliveryLocationNote = $meta['delivery_location_note'] ?? null;

        if ($serviceType === PosServiceType::Delivery && isset($meta['storefront_customer_id'])) {
            $customer = StorefrontCustomer::query()->find($meta['storefront_customer_id']);

            if ($customer !== null) {
                if (filled($meta['saved_address_id'] ?? null)) {
                    $saved = app(StorefrontCustomerAddressService::class)
                        ->findForCustomer($customer, (int) $meta['saved_address_id']);

                    if ($saved !== null) {
                        $savedAddressId = $saved->getKey();
                        $deliveryLatitude = (float) $saved->latitude;
                        $deliveryLongitude = (float) $saved->longitude;
                        $deliveryLocationNote = $saved->location_note;
                    }
                } elseif (($meta['save_address'] ?? false) && filled($meta['address_label'] ?? null)) {
                    $saved = app(StorefrontCustomerAddressService::class)->saveFromCheckout(
                        $customer,
                        (string) $meta['address_label'],
                        (float) $deliveryLatitude,
                        (float) $deliveryLongitude,
                        (string) $deliveryLocationNote,
                    );
                    $savedAddressId = $saved->getKey();
                }
            }
        }

        $order = $this->orders->createWithItems([
            'status' => OrderStatus::New,
            'source' => OrderSource::Website,
            'service_type' => $serviceType,
            'total' => $total,
            'delivery_fee' => $deliveryFee > 0 ? $deliveryFee : null,
            'customer_name' => $meta['customer_name'] ?? null,
            'customer_phone' => $meta['customer_phone'] ?? null,
            'customer_note' => filled($meta['customer_note'] ?? null) ? $meta['customer_note'] : null,
            'delivery_latitude' => $deliveryLatitude,
            'delivery_longitude' => $deliveryLongitude,
            'delivery_location_note' => $deliveryLocationNote,
            'storefront_customer_id' => $meta['storefront_customer_id'] ?? null,
            'saved_address_id' => $savedAddressId,
        ], $items);

        WebsiteOrderPlaced::dispatch($order->getKey());

        return [
            'order_id' => $order->getKey(),
            'total' => $total,
            'service_type' => $serviceType->value,
            'table_number' => null,
        ];
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     * @return array{order_id: int, total: float, whatsapp_url: string}
     *
     * @deprecated Use placeCustomerOrder() for website checkout.
     */
    public function placeGuestOrder(
        array $lines,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?string $customerNote = null,
    ): array {
        $result = $this->placeCustomerOrder($lines, [
            'service_type' => PosServiceType::Takeaway->value,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_note' => $customerNote,
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'delivery_location_note' => 'طلب قديم — بدون موقع',
        ]);

        return [
            'order_id' => $result['order_id'],
            'total' => $result['total'],
            'whatsapp_url' => '',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{0: Collection<int, array<string, mixed>>, 1: float}
     */
    protected function buildOrderItems(array $lines): array
    {
        $normalized = collect($lines)->map(function (array $line): array {
            $row = [
                'product_id' => (int) $line['product_id'],
                'quantity' => max(1, (int) ($line['quantity'] ?? 1)),
            ];

            if (array_key_exists('product_variant_id', $line) && $line['product_variant_id'] !== null && $line['product_variant_id'] !== '') {
                $row['product_variant_id'] = (int) $line['product_variant_id'];
            }

            if (! empty($line['addons']) && is_array($line['addons'])) {
                $row['addons'] = collect($line['addons'])->map(fn (array $addon): array => [
                    'product_addon_id' => (int) $addon['product_addon_id'],
                    'quantity' => max(1, (int) ($addon['quantity'] ?? 1)),
                ])->values()->all();
            }

            return $row;
        })->values()->all();

        [, $resolved, $total] = $this->options->prepareOrderLines($normalized);

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

        return [$items, $total];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function assertDeliveryProductsAllowed(array $lines): void
    {
        $productIds = collect($lines)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return;
        }

        $blocked = Product::query()
            ->published()
            ->whereIn('id', $productIds->all())
            ->where('is_delivery_available', false)
            ->pluck('name')
            ->values();

        if ($blocked->isNotEmpty()) {
            throw new InvalidArgumentException(
                'هذه الأصناف غير متاحة للتوصيل: '.$blocked->join('، '),
            );
        }
    }
}
