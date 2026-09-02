<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\System\AdminController;

use App\Enums\OrderType;
use App\Http\Requests\Admin\Sales\Order\StoreOrderRequest;
use App\Http\Requests\Admin\Sales\Order\UpdateOrderRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $orders = $this->forBranch(Order::query(), $request)
            ->where('type', OrderType::Delivery)
            ->with([
                'customer:id,name,phone',
                'invoices:id,order_id,invoice_number,status,total',
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $orders->getCollection()->transform(fn (Order $order) => $this->formatOrderSummary($order));

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Order::class);

        return Inertia::render('admin/orders/create');
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', Order::class);

        Order::create($request->validated());

        return to_route('admin.orders.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.order_created')]);
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        $order->loadMissing([
            'customer.addresses',
            'user:id,name',
            'items.product:id,name',
            'invoices:id,order_id,invoice_number,status,total,paid,created_at',
        ]);

        return Inertia::render('admin/orders/show', [
            'order' => $this->formatOrderDetail($order),
        ]);
    }

    public function edit(Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $invoice = $order->invoices()
            ->where('status', '!=', 'void')
            ->latest()
            ->first();

        if ($invoice !== null) {
            return redirect()->route('pos.invoices.edit', $invoice);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('toast', ['type' => 'warning', 'message' => __('toast.no_editable_invoice')]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $order->update($request->validated());

        return to_route('admin.orders.show', $order)
            ->with('toast', ['type' => 'success', 'message' => __('toast.order_updated')]);
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);

        $order->delete();

        return to_route('admin.orders.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.order_deleted')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOrderSummary(Order $order): array
    {
        $invoice = $order->invoices->first(function ($item) {
            $status = $item->status instanceof \BackedEnum ? $item->status->value : (string) $item->status;

            return $status !== 'void';
        });

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'type' => $order->type->value,
            'status' => $order->status->value,
            'source' => $order->source,
            'total' => (float) $order->total,
            'created_at' => $order->created_at?->toIso8601String(),
            'customer_name' => $order->customer?->name,
            'invoice' => $invoice ? [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status->value ?? $invoice->status,
                'total' => (float) $invoice->total,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOrderDetail(Order $order): array
    {
        $summary = $this->formatOrderSummary($order);
        $delivery = $this->parseDeliveryAddress($order->delivery_address);

        return [
            ...$summary,
            'notes' => $order->notes,
            'rejection_reason' => $order->rejection_reason,
            'delivery_address' => $order->delivery_address,
            'delivery' => $delivery,
            'subtotal' => (float) $order->subtotal,
            'tax_amount' => (float) $order->tax_amount,
            'discount_amount' => (float) $order->discount_amount,
            'delivery_fee' => (float) $order->delivery_fee,
            'cashier_name' => $order->user?->name,
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'phone' => $order->customer->phone,
                'whatsapp' => $order->customer->whatsapp,
                'email' => $order->customer->email,
                'loyalty_points' => (int) $order->customer->loyalty_points,
                'addresses' => $order->customer->addresses->map(fn ($address) => [
                    'id' => $address->id,
                    'label' => $address->label,
                    'formatted' => $address->formatted(),
                    'city' => $address->city,
                    'district' => $address->district,
                    'street' => $address->street,
                    'building' => $address->building,
                    'floor' => $address->floor,
                    'apartment' => $address->apartment,
                    'notes' => $address->notes,
                    'lat' => $address->lat !== null ? (float) $address->lat : null,
                    'lng' => $address->lng !== null ? (float) $address->lng : null,
                    'is_default' => (bool) $address->is_default,
                    'maps_url' => $address->lat !== null && $address->lng !== null
                        ? "https://www.google.com/maps?q={$address->lat},{$address->lng}"
                        : null,
                ])->values(),
            ] : null,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->product?->name ?? 'منتج',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => round((float) $item->quantity * (float) $item->unit_price, 2),
            ])->values(),
            'invoices' => $order->invoices->map(fn ($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status->value ?? $invoice->status,
                'total' => (float) $invoice->total,
                'paid' => (float) $invoice->paid,
                'created_at' => $invoice->created_at?->toIso8601String(),
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseDeliveryAddress(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [
                'raw' => null,
                'address' => null,
                'lat' => null,
                'lng' => null,
                'location_notes' => null,
                'maps_url' => null,
            ];
        }

        $address = null;
        $lat = null;
        $lng = null;
        $locationNotes = null;

        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim($line);

            if (str_starts_with($line, 'العنوان:')) {
                $address = trim(substr($line, strlen('العنوان:')));
            } elseif (str_starts_with($line, 'الإحداثيات:')) {
                $coords = trim(substr($line, strlen('الإحداثيات:')));

                if (preg_match('/([\d.-]+)\s*,\s*([\d.-]+)/', $coords, $matches)) {
                    $lat = (float) $matches[1];
                    $lng = (float) $matches[2];
                }
            } elseif (str_starts_with($line, 'ملاحظات الموقع:')) {
                $locationNotes = trim(substr($line, strlen('ملاحظات الموقع:')));
            }
        }

        if ($address === null && $lat === null && ! str_contains($text, 'العنوان:')) {
            $address = trim($text);
        }

        return [
            'raw' => $text,
            'address' => $address,
            'lat' => $lat,
            'lng' => $lng,
            'location_notes' => $locationNotes,
            'maps_url' => $lat !== null && $lng !== null
                ? "https://www.google.com/maps?q={$lat},{$lng}"
                : null,
        ];
    }
}
