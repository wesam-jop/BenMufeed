<?php

namespace App\Services\Storefront;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Events\OrderCreated;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOtpVerification;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuestCheckoutService
{
    public function __construct(
        private CartService $cart,
        private StorefrontService $storefront,
        private NumberGeneratorService $numberGenerator,
        private GuestProfileService $guestProfile,
    ) {}

    public function placeOrder(OrderOtpVerification $verification): Order
    {
        $summary = $this->cart->summary();

        if ($summary['count'] === 0) {
            throw ValidationException::withMessages([
                'cart' => ['السلة فارغة.'],
            ]);
        }

        if (! $verification->isVerified()) {
            throw ValidationException::withMessages([
                'otp' => ['يجب التحقق من رقم الواتساب أولاً.'],
            ]);
        }

        if (empty($summary['location_lat']) || empty($summary['location_lng'])) {
            throw ValidationException::withMessages([
                'location' => ['يرجى تحديد موقع التوصيل على الخريطة.'],
            ]);
        }

        app(OpeningHoursService::class)->ensureOpenForDelivery();

        $branch = $this->storefront->defaultBranch();

        if ($branch === null) {
            throw ValidationException::withMessages([
                'branch' => ['لا يوجد فرع متاح حالياً.'],
            ]);
        }

        return DB::transaction(function () use ($verification, $branch, $summary) {
            $customer = Customer::query()->firstOrCreate(
                ['phone' => str_starts_with($verification->phone, '966')
                    ? '0'.substr($verification->phone, 3)
                    : $verification->phone],
                [
                    'name' => $verification->name,
                    'whatsapp' => $verification->phone,
                    'is_active' => true,
                ],
            );

            if ($customer->name !== $verification->name) {
                $customer->update(['name' => $verification->name]);
            }

            if ($customer->whatsapp !== $verification->phone) {
                $customer->update(['whatsapp' => $verification->phone]);
            }

            $order = Order::create([
                'branch_id' => $branch->id,
                'order_number' => $this->numberGenerator->next('ORD', Order::class, 'order_number'),
                'type' => OrderType::Delivery,
                'status' => OrderStatus::Pending,
                'customer_id' => $customer->id,
                'source' => 'online',
                'delivery_address' => $this->cart->deliveryAddressText(),
                'notes' => $summary['location_notes'] ?? null,
            ]);

            foreach ($this->cart->lineItems() as $itemData) {
                $product = Product::with('taxRate')->findOrFail($itemData['product_id']);
                $variant = isset($itemData['variant_id'])
                    ? ProductVariant::find($itemData['variant_id'])
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
                    'status' => OrderItemStatus::Pending,
                ]);
            }

            $this->recalculateOrderTotals($order);
            $this->cart->clear();
            $this->saveDeliveryAddress($customer, $summary);
            $this->guestProfile->clearSession();
            Auth::guard('customer')->login($customer);

            OrderCreated::dispatch($order->fresh(['items.product']));

            return $order;
        });
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

    private function saveDeliveryAddress(Customer $customer, array $summary): void
    {
        if (empty($summary['location_lat']) || empty($summary['location_lng'])) {
            return;
        }

        $lat = round((float) $summary['location_lat'], 7);
        $lng = round((float) $summary['location_lng'], 7);
        $street = trim((string) ($summary['location_address'] ?? '')) ?: 'موقع على الخريطة';

        $existing = $customer->addresses()
            ->where('lat', $lat)
            ->where('lng', $lng)
            ->first();

        if ($existing !== null) {
            $existing->update([
                'street' => $street,
                'notes' => $summary['location_notes'] ?? $existing->notes,
            ]);

            if (! $customer->addresses()->where('is_default', true)->where('id', '!=', $existing->id)->exists()) {
                $customer->addresses()->update(['is_default' => false]);
                $existing->update(['is_default' => true]);
            }

            return;
        }

        $isFirst = ! $customer->addresses()->exists();

        if ($isFirst) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $customer->addresses()->create([
            'label' => 'موقع التوصيل',
            'city' => '—',
            'street' => $street,
            'notes' => $summary['location_notes'] ?? null,
            'lat' => $lat,
            'lng' => $lng,
            'is_default' => $isFirst,
        ]);
    }
}
