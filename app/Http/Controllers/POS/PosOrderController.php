<?php

namespace App\Http\Controllers\POS;

use App\Actions\CompleteSaleAction;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RestaurantTable;
use App\Models\TableSession;
use App\Models\Warehouse;
use App\Enums\TableStatus;
use App\Services\Accounting\CashRegisterService;
use App\Services\Shared\NumberGeneratorService;
use App\Services\Sales\TableGuestSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PosOrderController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numberGenerator,
        private CashRegisterService $cashRegisterService,
        private TableGuestSessionService $tableSessions,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:'.implode(',', array_column(OrderType::cases(), 'value')),
            'table_session_id' => 'nullable|exists:table_sessions,id',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'delivery_address' => 'nullable|string|max:1000',
            'delivery_zone_id' => 'nullable|exists:delivery_zones,id',
            'delivery_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $type = OrderType::from($validated['type']);
        $customerId = $validated['customer_id'] ?? null;
        $deliveryFee = (float) ($validated['delivery_fee'] ?? 0);

        if ($type === OrderType::Delivery) {
            $request->validate([
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'delivery_address' => 'required|string|max:1000',
            ]);

            $customer = Customer::query()->firstOrCreate(
                ['phone' => $validated['customer_phone']],
                [
                    'name' => $validated['customer_name'],
                    'whatsapp' => $validated['customer_phone'],
                    'is_active' => true,
                ],
            );

            if ($customer->name !== $validated['customer_name']) {
                $customer->update(['name' => $validated['customer_name']]);
            }

            $customerId = $customer->id;

            if (isset($validated['delivery_zone_id'])) {
                $zone = DeliveryZone::findOrFail($validated['delivery_zone_id']);
                $deliveryFee = (float) $zone->fee;
            }
        }

        $status = match ($type) {
            OrderType::Takeaway, OrderType::Delivery => OrderStatus::Confirmed,
            default => OrderStatus::Pending,
        };

        $order = Order::create([
            'branch_id' => $user->branch_id,
            'order_number' => $this->numberGenerator->next('ORD', Order::class, 'order_number'),
            'type' => $type,
            'status' => $status,
            'table_session_id' => $validated['table_session_id'] ?? null,
            'customer_id' => $customerId,
            'user_id' => $user->id,
            'source' => 'pos',
            'notes' => $validated['notes'] ?? null,
            'delivery_address' => $validated['delivery_address'] ?? null,
            'delivery_fee' => $deliveryFee,
        ]);

        return response()->json(['order' => $order->load('items.product')], 201);
    }

    public function addItem(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|numeric|min:0.0001',
            'modifiers' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled], true)) {
            return response()->json(['message' => __('toast.order_locked')], 422);
        }

        $product = Product::with('taxRate')->findOrFail($validated['product_id']);
        $variant = isset($validated['variant_id'])
            ? ProductVariant::findOrFail($validated['variant_id'])
            : null;

        $unitPrice = (float) ($variant?->sale_price ?? $product->sale_price);
        $costPrice = (float) ($variant?->cost_price ?? $product->cost_price);
        $quantity = (float) $validated['quantity'];

        $existing = OrderItem::query()
            ->where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->when(
                $variant?->id,
                fn ($q) => $q->where('variant_id', $variant->id),
                fn ($q) => $q->whereNull('variant_id'),
            )
            ->first();

        if ($existing) {
            $newQuantity = (float) $existing->quantity + $quantity;
            $lineSubtotal = $unitPrice * $newQuantity;

            $existing->update([
                'quantity' => $newQuantity,
                'tax' => $this->calculateTax($product, $lineSubtotal),
            ]);

            $this->recalculateOrderTotals($order);

            return response()->json([
                'item' => $existing->fresh(['product', 'variant']),
                'order' => $order->fresh(['items.product']),
            ]);
        }

        $lineSubtotal = $unitPrice * $quantity;
        $tax = $this->calculateTax($product, $lineSubtotal);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'cost_price' => $costPrice,
            'tax' => $tax,
            'modifiers' => $validated['modifiers'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => OrderItemStatus::Pending,
        ]);

        $this->recalculateOrderTotals($order);

        return response()->json([
            'item' => $item->load('product', 'variant'),
            'order' => $order->fresh(['items.product']),
        ], 201);
    }

    public function updateItem(Request $request, Order $order, OrderItem $item): JsonResponse
    {
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.0001',
        ]);

        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled], true)) {
            return response()->json(['message' => __('toast.order_locked')], 422);
        }

        $product = Product::with('taxRate')->findOrFail($item->product_id);
        $quantity = (float) $validated['quantity'];
        $lineSubtotal = (float) $item->unit_price * $quantity;

        $item->update([
            'quantity' => $quantity,
            'tax' => $this->calculateTax($product, $lineSubtotal),
        ]);

        $this->recalculateOrderTotals($order);

        return response()->json(['order' => $order->fresh(['items.product'])]);
    }

    public function removeItem(Order $order, OrderItem $item): JsonResponse
    {
        if ($item->order_id !== $order->id) {
            abort(404);
        }

        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled], true)) {
            return response()->json(['message' => __('toast.order_locked')], 422);
        }

        $item->delete();
        $this->recalculateOrderTotals($order);

        return response()->json(['order' => $order->fresh(['items.product'])]);
    }

    public function applyDiscount(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled], true)) {
            return response()->json(['message' => __('toast.order_locked')], 422);
        }

        $order->update([
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'discount_percent' => $validated['discount_percent'] ?? 0,
        ]);

        $this->recalculateOrderTotals($order);

        return response()->json(['order' => $order->fresh(['items.product'])]);
    }

    public function sendToKitchen(Order $order): JsonResponse
    {
        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled], true)) {
            return response()->json(['message' => __('toast.order_locked')], 422);
        }

        if ($order->items()->count() === 0) {
            return response()->json(['message' => __('toast.order_empty')], 422);
        }

        $order->update(['status' => OrderStatus::Preparing]);

        $order = $order->fresh(['items.product', 'tableSession.table']);

        if ($order->tableSession?->table) {
            $this->tableSessions->markOccupied($order->tableSession->table);
        }

        return response()->json(['order' => $order]);
    }

    public function complete(Request $request, Order $order, CompleteSaleAction $action): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.reference' => 'nullable|string|max:255',
        ], [
            'warehouse_id.required' => 'يجب تحديد المستودع.',
            'warehouse_id.exists' => 'المستودع المحدد غير موجود.',
            'payments.required' => 'يجب إدخال طريقة الدفع.',
            'payments.min' => 'يجب إدخال دفعة واحدة على الأقل.',
            'payments.*.amount.required' => 'يجب إدخال مبلغ الدفع.',
            'payments.*.amount.min' => 'مبلغ الدفع يجب أن يكون أكبر من صفر.',
        ]);

        try {
            $order = $this->prepareOrderForCompletion($order);

            $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
            $register = $this->cashRegisterService->resolveRegisterForUser($request->user());
            $session = $this->cashRegisterService->getOrCreateDailySession($register, $request->user());

            $invoice = $action->execute(
                order: $order,
                warehouse: $warehouse,
                payments: $validated['payments'],
                session: $session,
                user: $request->user(),
            );

            if ($order->table_session_id) {
                $tableSession = TableSession::query()->find($order->table_session_id);

                if ($tableSession !== null) {
                    $tableSession->update([
                        'status' => 'closed',
                        'closed_at' => now(),
                    ]);

                    RestaurantTable::query()
                        ->where('id', $tableSession->table_id)
                        ->update(['status' => TableStatus::Free->value]);
                }
            }

            return response()->json(['invoice' => $invoice]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function prepareOrderForCompletion(Order $order): Order
    {
        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled, OrderStatus::Rejected], true)) {
            throw new RuntimeException('لا يمكن إتمام هذا الطلب.');
        }

        if ($order->items()->count() === 0) {
            throw new RuntimeException('الطلب لا يحتوي على منتجات.');
        }

        if (
            $order->source === 'pos'
            && in_array($order->status, [OrderStatus::Pending, OrderStatus::Ready], true)
        ) {
            $order->update(['status' => OrderStatus::Confirmed]);
        }

        return $order->fresh(['items.product']);
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

        $discountAmount = (float) $order->discount_amount;

        if ((float) $order->discount_percent > 0) {
            $discountAmount += round($subtotal * ((float) $order->discount_percent / 100), 2);
        }

        $total = max(0, $subtotal - $discountAmount + $taxAmount + (float) $order->delivery_fee);

        $order->update([
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'discount_amount' => round($discountAmount, 2),
            'total' => round($total, 2),
        ]);
    }
}
