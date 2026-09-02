<?php

namespace App\Http\Controllers\Kitchen;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KitchenDisplayController extends Controller
{
    /** @var list<string> */
    private const KITCHEN_STATUSES = [
        'preparing',
        'confirmed',
        'ready',
    ];

    public function index(Request $request): Response|JsonResponse
    {
        $branchId = $request->user()?->branch_id;
        $status = $request->query('status', OrderStatus::Preparing->value);

        if (! in_array($status, self::KITCHEN_STATUSES, true)) {
            $status = OrderStatus::Preparing->value;
        }

        $orders = Order::query()
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->where('status', $status)
            ->with(['items.product', 'items.variant', 'tableSession.table', 'customer:id,name'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Order $order) => $this->formatKitchenOrder($order));

        if ($request->expectsJson()) {
            return response()->json(['orders' => $orders, 'status' => $status]);
        }

        return Inertia::render('kitchen/display', [
            'orders' => $orders,
            'status' => $status,
            'statuses' => self::KITCHEN_STATUSES,
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:'.implode(',', array_column(OrderStatus::cases(), 'value')),
            'item_id' => 'nullable|exists:order_items,id',
            'item_status' => 'nullable|string|in:'.implode(',', array_column(OrderItemStatus::cases(), 'value')),
        ]);

        if (isset($validated['item_id'], $validated['item_status'])) {
            $item = OrderItem::where('order_id', $order->id)
                ->findOrFail($validated['item_id']);

            $item->update(['status' => $validated['item_status']]);
        }

        $order->update(['status' => $validated['status']]);

        if ($request->expectsJson()) {
            return response()->json([
                'order' => $this->formatKitchenOrder($order->fresh(['items.product', 'tableSession.table', 'customer'])),
            ]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => __('toast.order_status_updated')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatKitchenOrder(Order $order): array
    {
        $type = $order->type instanceof \BackedEnum ? $order->type->value : (string) $order->type;
        $status = $order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'type' => $type,
            'status' => $status,
            'source' => $order->source,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toIso8601String(),
            'total' => (float) $order->total,
            'table_name' => $order->tableSession?->table?->name,
            'customer_name' => $order->customer?->name,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->product?->name ?? 'منتج',
                'quantity' => (float) $item->quantity,
                'status' => $item->status instanceof \BackedEnum ? $item->status->value : (string) $item->status,
                'notes' => $item->notes,
            ])->values(),
        ];
    }
}
