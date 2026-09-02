<?php

namespace App\Http\Controllers\POS;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Sales\TableGuestSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosTableOrderController extends Controller
{
    public function __construct(
        private TableGuestSessionService $sessions,
    ) {}

    public function accept(Request $request, Order $order): JsonResponse
    {
        $this->authorize('acceptTable', $order);

        if (
            $order->type !== OrderType::DineIn
            || $order->source !== 'qr'
            || $order->status !== OrderStatus::Pending
        ) {
            return response()->json(['message' => __('toast.cannot_accept_order')], 422);
        }

        $order->update(['status' => OrderStatus::Confirmed]);

        $order = $order->fresh(['items.product', 'tableSession.table']);

        if ($order->tableSession?->table) {
            $this->sessions->markOccupied($order->tableSession->table);
        }

        OrderStatusUpdated::dispatch($order);

        return response()->json([
            'order' => $order,
            'message' => __('toast.table_order_accepted'),
        ]);
    }

    public function reject(Request $request, Order $order): JsonResponse
    {
        $this->authorize('rejectTable', $order);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if (
            $order->type !== OrderType::DineIn
            || $order->source !== 'qr'
            || $order->status !== OrderStatus::Pending
        ) {
            return response()->json(['message' => __('toast.cannot_reject_order')], 422);
        }

        $order->update([
            'status' => OrderStatus::Rejected,
            'rejection_reason' => $validated['reason'] ?? 'تم رفض الطلب من نقطة البيع',
        ]);

        OrderStatusUpdated::dispatch($order->fresh());

        return response()->json([
            'order' => $order->fresh(['items.product', 'tableSession.table']),
            'message' => __('toast.table_order_rejected'),
        ]);
    }
}
