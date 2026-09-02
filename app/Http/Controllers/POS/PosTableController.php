<?php

namespace App\Http\Controllers\POS;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\TableSession;
use App\Services\Shared\NumberGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosTableController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numberGenerator,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('pos.terminal', ['mode' => 'tables']);
    }

    public function order(Request $request, RestaurantTable $table): JsonResponse|Response
    {
        $session = TableSession::query()
            ->where('table_id', $table->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (! $session) {
            $session = TableSession::create([
                'table_id' => $table->id,
                'status' => 'open',
                'guest_count' => $request->integer('guest_count') ?: null,
                'opened_at' => now(),
            ]);
            // Table stays Free until the order is sent to the kitchen
        }

        $order = Order::query()
            ->where('table_session_id', $session->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['items.product', 'items.variant'])
            ->latest()
            ->first();

        if (! $order) {
            $order = Order::create([
                'branch_id' => $request->user()->branch_id,
                'order_number' => $this->numberGenerator->next('ORD', Order::class, 'order_number'),
                'type' => OrderType::DineIn,
                'status' => OrderStatus::Pending,
                'table_session_id' => $session->id,
                'user_id' => $request->user()->id,
                'source' => 'pos',
            ]);

            $order->load(['items.product', 'items.variant']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'table' => $table,
                'session' => $session,
                'order' => $order,
            ]);
        }

        return Inertia::render('pos/table-order', [
            'table' => $table,
            'session' => $session,
            'order' => $order,
        ]);
    }
}
