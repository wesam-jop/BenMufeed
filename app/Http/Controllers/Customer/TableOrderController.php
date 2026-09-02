<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ActivateTableSessionRequest;
use App\Http\Requests\Customer\StoreTableOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\RestaurantTable;
use App\Models\TableSession;
use App\Services\Shared\NumberGeneratorService;
use App\Services\Storefront\StorefrontService;
use App\Services\Sales\TableGuestSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Cookie;

class TableOrderController extends Controller
{
    public function __construct(
        protected TableGuestSessionService $sessions,
        protected NumberGeneratorService $numberGenerator,
        protected StorefrontService $storefront,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $active = $this->sessions->findActiveByToken(
            $request->cookie(TableGuestSessionService::COOKIE_NAME)
        );

        if ($active) {
            return redirect()->route('table-order.menu');
        }

        $branch = $this->storefront->defaultBranch();
        $tables = RestaurantTable::query()
            ->when($branch, fn ($q) => $q->where('branch_id', $branch->id))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'zone', 'status', 'capacity']);

        return Inertia::render('customer/table-order/activate', [
            'business' => $this->storefront->businessInfo($branch),
            'tables' => $tables->map(function (RestaurantTable $table) {
                $isOccupied = $this->sessions->tableIsOccupied($table);
                $table->refresh();

                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'zone' => $table->zone,
                    'status' => $table->status instanceof \BackedEnum
                        ? $table->status->value
                        : (string) $table->status,
                    'capacity' => $table->capacity,
                    'is_available' => ! $isOccupied,
                    'availability_label' => $isOccupied ? 'مفتوحة / مشغولة' : 'متاحة',
                ];
            }),
        ]);
    }

    public function activate(ActivateTableSessionRequest $request): RedirectResponse
    {
        $table = RestaurantTable::query()->findOrFail($request->integer('table_id'));
        $session = $this->sessions->activate(
            $table,
            (string) $request->input('access_code'),
            $request->integer('guest_count') ?: null,
        );

        return redirect()
            ->route('table-order.menu')
            ->with('toast', [
                'type' => 'success',
                'message' => __('toast.table_activated'),
            ])
            ->withCookie($this->sessionCookie($session));
    }

    public function menu(Request $request): Response
    {
        /** @var TableSession $session */
        $session = $request->attributes->get('tableGuestSession');
        $session->loadMissing('table');

        $branchId = $session->table->branch_id;

        $categories = ProductCategory::query()
            ->forBranch($branchId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['products' => fn ($q) => $q
                ->where('is_active', true)
                ->with(['variants', 'taxRate'])
                ->orderBy('name'),
            ])
            ->get();

        $orders = Order::query()
            ->where('table_session_id', $session->id)
            ->with(['items.product'])
            ->latest()
            ->get()
            ->map(fn (Order $order) => $this->formatOrder($order));

        return Inertia::render('customer/table-order/menu', [
            'business' => $this->storefront->businessInfo($session->table->branch),
            'session' => [
                'id' => $session->id,
                'table_name' => $session->table->name,
                'expires_at' => $session->expires_at?->toIso8601String(),
                'remaining_seconds' => $session->remainingSeconds(),
                'guest_count' => $session->guest_count,
            ],
            'categories' => $categories->map(fn (ProductCategory $cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'products' => $cat->products->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'sale_price' => (float) $product->sale_price,
                    'image' => $product->image_url ?? $product->image,
                    'category' => $cat->name,
                ]),
            ]),
            'orders' => $orders,
        ]);
    }

    public function storeOrder(StoreTableOrderRequest $request): RedirectResponse
    {
        /** @var TableSession $session */
        $session = $request->attributes->get('tableGuestSession');
        $this->sessions->ensureActive($session);
        $session->loadMissing('table');

        $validated = $request->validated();

        $order = Order::create([
            'branch_id' => $session->table->branch_id,
            'order_number' => $this->numberGenerator->next('ORD', Order::class, 'order_number'),
            'type' => OrderType::DineIn,
            'status' => OrderStatus::Pending,
            'table_session_id' => $session->id,
            'customer_id' => auth('customer')->id(),
            'source' => 'qr',
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $itemData) {
            $product = Product::with('taxRate')->findOrFail($itemData['product_id']);
            $variant = isset($itemData['variant_id'])
                ? ProductVariant::findOrFail($itemData['variant_id'])
                : null;

            $unitPrice = (float) ($variant?->sale_price ?? $product->sale_price);
            $quantity = (float) $itemData['quantity'];
            $lineSubtotal = $unitPrice * $quantity;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'cost_price' => (float) ($variant?->cost_price ?? $product->cost_price),
                'tax' => $this->calculateTax($product, $lineSubtotal),
                'modifiers' => $itemData['modifiers'] ?? null,
                'notes' => $itemData['notes'] ?? null,
                'status' => OrderItemStatus::Pending,
            ]);
        }

        $this->recalculateOrderTotals($order);
        OrderCreated::dispatch($order->fresh(['items.product', 'tableSession.table']));

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.table_order_sent'),
        ]);
    }

    public function orders(Request $request): Response
    {
        /** @var TableSession $session */
        $session = $request->attributes->get('tableGuestSession');
        $session->loadMissing('table');

        $orders = Order::query()
            ->where('table_session_id', $session->id)
            ->with(['items.product'])
            ->latest()
            ->get()
            ->map(fn (Order $order) => $this->formatOrder($order));

        return Inertia::render('customer/table-order/orders', [
            'business' => $this->storefront->businessInfo($session->table->branch),
            'session' => [
                'id' => $session->id,
                'table_name' => $session->table->name,
                'expires_at' => $session->expires_at?->toIso8601String(),
                'remaining_seconds' => $session->remainingSeconds(),
            ],
            'orders' => $orders,
        ]);
    }

    public function end(Request $request): RedirectResponse
    {
        return redirect()
            ->route('table-order.index')
            ->withCookie(Cookie::forget(TableGuestSessionService::COOKIE_NAME));
    }

    private function sessionCookie(TableSession $session): Cookie
    {
        return cookie(
            TableGuestSessionService::COOKIE_NAME,
            $session->session_token,
            TableGuestSessionService::SESSION_HOURS * 60,
            '/',
            null,
            false,
            true,
            false,
            'lax',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOrder(Order $order): array
    {
        $status = $order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $status,
            'total' => (float) $order->total,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toIso8601String(),
            'rejection_reason' => $order->rejection_reason,
            'items' => $order->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'name' => $item->product?->name ?? 'منتج',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ]),
        ];
    }

    private function calculateTax(Product $product, float $lineSubtotal): float
    {
        $taxRate = $product->taxRate;

        if (! $taxRate || ! $taxRate->is_active) {
            return 0;
        }

        $rate = (float) $taxRate->rate;

        if ($taxRate->is_inclusive) {
            return round($lineSubtotal - ($lineSubtotal / (1 + ($rate / 100))), 2);
        }

        return round($lineSubtotal * ($rate / 100), 2);
    }

    private function recalculateOrderTotals(Order $order): void
    {
        $order->load('items');

        $subtotal = $order->items->sum(fn (OrderItem $item) => ((float) $item->unit_price * (float) $item->quantity) - (float) $item->discount);
        $taxAmount = $order->items->sum(fn (OrderItem $item) => (float) $item->tax);
        $total = max(0, $subtotal + $taxAmount);

        $order->update([
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
        ]);
    }
}
