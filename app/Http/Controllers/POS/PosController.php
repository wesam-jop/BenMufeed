<?php

namespace App\Http\Controllers\POS;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RestaurantTable;
use App\Models\Warehouse;
use App\Services\Accounting\CashRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PosController extends Controller
{
    public function __construct(
        private CashRegisterService $cashRegisterService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $branchId = $user?->branch_id;
        $dailySession = null;

        try {
            $register = $this->cashRegisterService->resolveRegisterForUser($user);
            $session = $this->cashRegisterService->getOrCreateDailySession($register, $user);
            $dailySession = $this->cashRegisterService->dailySummary($session);
        } catch (RuntimeException) {
            // No active register for branch
        }

        $categories = ProductCategory::query()
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        $products = Product::query()
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->where('is_active', true)
            ->whereIn('type', [ProductType::Finished, ProductType::Combo, ProductType::Service])
            ->where('sale_price', '>', 0)
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'sale_price', 'image', 'category_id']);

        $warehouse = Warehouse::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('is_active', true)
            ->where('name', 'المستودع الرئيسي')
            ->first()
            ?? Warehouse::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('is_active', true)
                ->first();

        $tables = RestaurantTable::query()
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['sessions' => fn ($q) => $q
                ->where('status', 'open')
                ->withCount(['orders' => fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled'])]),
            ])
            ->get(['id', 'name', 'capacity', 'zone', 'status']);

        $pendingDeliveryOrders = Order::query()
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->where('type', OrderType::Delivery)
            ->where('status', OrderStatus::Pending)
            ->where('source', 'online')
            ->with(['customer:id,name,phone,whatsapp', 'items.product:id,name'])
            ->latest()
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'total' => (float) $order->total,
                'subtotal' => (float) $order->subtotal,
                'tax_amount' => (float) $order->tax_amount,
                'delivery_fee' => (float) $order->delivery_fee,
                'delivery_address' => $order->delivery_address,
                'notes' => $order->notes,
                'created_at' => $order->created_at?->toIso8601String(),
                'customer' => $order->customer ? [
                    'name' => $order->customer->name,
                    'phone' => $order->customer->phone,
                    'whatsapp' => $order->customer->whatsapp,
                ] : null,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->product?->name ?? 'منتج',
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                ])->values(),
            ])
            ->values();

        $pendingTableOrders = Order::query()
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->where('type', OrderType::DineIn)
            ->where('status', OrderStatus::Pending)
            ->where('source', 'qr')
            ->with(['tableSession.table:id,name', 'items.product:id,name'])
            ->latest()
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'total' => (float) $order->total,
                'subtotal' => (float) $order->subtotal,
                'tax_amount' => (float) $order->tax_amount,
                'notes' => $order->notes,
                'created_at' => $order->created_at?->toIso8601String(),
                'table_name' => $order->tableSession?->table?->name,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->product?->name ?? 'منتج',
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                ])->values(),
            ])
            ->values();

        return Inertia::render('pos/terminal', [
            'branchId' => $branchId,
            'dailySession' => $dailySession,
            'categories' => $categories,
            'products' => $products,
            'warehouseId' => $warehouse?->id,
            'tables' => $tables,
            'pendingDeliveryOrders' => $pendingDeliveryOrders,
            'pendingTableOrders' => $pendingTableOrders,
            'initialMode' => in_array($request->query('mode'), ['takeaway', 'delivery', 'tables', 'table-qr'], true)
                ? $request->query('mode')
                : 'takeaway',
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $branchId = $request->user()?->branch_id;

        $categories = ProductCategory::query()
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['products' => fn ($q) => $q
                ->where('is_active', true)
                ->with(['variants', 'taxRate', 'modifierGroups.options'])
                ->orderBy('name'),
            ])
            ->get();

        $products = Product::query()
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->where('is_active', true)
            ->with(['category', 'variants', 'taxRate', 'modifierGroups.options'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'categories' => $categories,
            'products' => $products,
        ]);
    }
}
