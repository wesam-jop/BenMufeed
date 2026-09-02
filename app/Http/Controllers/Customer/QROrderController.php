<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\RestaurantTable;
use App\Models\TableSession;
use App\Services\Shared\NumberGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QROrderController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numberGenerator,
    ) {}

    public function show(string $qrToken): Response
    {
        $table = RestaurantTable::where('qr_token', $qrToken)
            ->with('branch')
            ->firstOrFail();

        $session = TableSession::query()
            ->where('table_id', $table->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        $categories = ProductCategory::query()
            ->forBranch($table->branch_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['products' => fn ($q) => $q
                ->where('is_active', true)
                ->with(['variants', 'taxRate'])
                ->orderBy('name'),
            ])
            ->get();

        return Inertia::render('customer/qr-order', [
            'table' => $table,
            'session' => $session,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request, string $qrToken): JsonResponse
    {
        $table = RestaurantTable::where('qr_token', $qrToken)->firstOrFail();

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.modifiers' => 'nullable|array',
            'items.*.notes' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'guest_count' => 'nullable|integer|min:1|max:99',
        ]);

        $session = TableSession::query()
            ->where('table_id', $table->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (! $session) {
            $session = TableSession::create([
                'table_id' => $table->id,
                'status' => 'open',
                'guest_count' => $validated['guest_count'] ?? null,
                'opened_at' => now(),
            ]);
            // Occupied only after cashier accepts and sends to kitchen
        }

        $order = Order::create([
            'branch_id' => $table->branch_id,
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

        return response()->json(['order' => $order->fresh(['items.product'])], 201);
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
