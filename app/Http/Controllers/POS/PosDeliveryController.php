<?php

namespace App\Http\Controllers\POS;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosDeliveryController extends Controller
{
    public function accept(Request $request, Order $order): JsonResponse
    {
        $this->authorize('acceptDelivery', $order);

        if ($order->type !== OrderType::Delivery || $order->status !== OrderStatus::Pending) {
            return response()->json(['message' => __('toast.cannot_accept_order')], 422);
        }

        $order->update(['status' => OrderStatus::Confirmed]);

        OrderStatusUpdated::dispatch($order->fresh());

        return response()->json([
            'order' => $order->fresh(['customer', 'items.product']),
            'message' => __('toast.order_accepted'),
        ]);
    }

    public function reject(Request $request, Order $order): JsonResponse
    {
        $this->authorize('rejectDelivery', $order);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if ($order->type !== OrderType::Delivery || $order->status !== OrderStatus::Pending) {
            return response()->json(['message' => __('toast.cannot_reject_order')], 422);
        }

        $order->update([
            'status' => OrderStatus::Rejected,
            'rejection_reason' => $validated['reason'] ?? 'تم رفض الطلب من نقطة البيع',
        ]);

        OrderStatusUpdated::dispatch($order->fresh());

        return response()->json([
            'order' => $order->fresh(['customer', 'items.product']),
            'message' => __('toast.order_rejected'),
        ]);
    }
}
