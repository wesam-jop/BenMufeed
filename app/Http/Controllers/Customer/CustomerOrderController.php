<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Shared\NumberGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerOrderController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numberGenerator,
    ) {}

    public function index(): Response
    {
        $orders = auth('customer')->user()
            ->orders()
            ->latest()
            ->with(['items.product', 'items.variant'])
            ->paginate(15);

        return Inertia::render('customer/orders/index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order): Response
    {
        $this->authorizeOrder($order);

        $order->load(['items.product', 'items.variant']);

        return Inertia::render('customer/orders/show', [
            'order' => $order,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'type' => 'nullable|string|in:'.implode(',', array_column(OrderType::cases(), 'value')),
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.modifiers' => 'nullable|array',
            'items.*.notes' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'delivery_address' => 'nullable|string|max:1000',
        ]);

        $customer = auth('customer')->user();
        $type = $validated['type'] ?? OrderType::Online->value;

        $order = Order::create([
            'branch_id' => $validated['branch_id'],
            'order_number' => $this->numberGenerator->next('ORD', Order::class, 'order_number'),
            'type' => $type,
            'status' => OrderStatus::Pending,
            'customer_id' => $customer->id,
            'source' => 'online',
            'notes' => $validated['notes'] ?? null,
            'delivery_address' => $validated['delivery_address'] ?? null,
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

        return response()->json(['order' => $order->fresh(['items.product'])], 201);
    }

    private function authorizeOrder(Order $order): void
    {
        if ($order->customer_id !== auth('customer')->id()) {
            abort(403);
        }
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
        $total = max(0, $subtotal + $taxAmount + (float) $order->delivery_fee);

        $order->update([
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
        ]);
    }
}
