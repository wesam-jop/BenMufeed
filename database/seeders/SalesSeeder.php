<?php

namespace Database\Seeders;

use App\Enums\CashMovementType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\CashRegisterSession;
use App\Models\CashMovement;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\Concerns\InteractsWithMainBranch;
use Illuminate\Database\Seeder;

class SalesSeeder extends Seeder
{
    use InteractsWithMainBranch;

    public function run(): void
    {
        $branch = $this->mainBranch();
        $cashier = User::query()->where('username', 'cashier')->first() ?? $this->adminUser();
        $customer = Customer::query()->where('phone', '0501111111')->first();
        $register = $this->cashRegister();
        $latte = Product::query()->where('sku', 'MENU-LATTE')->first();
        $cappuccino = Product::query()->where('sku', 'MENU-CAPPUCCINO')->first();
        $cheesecake = Product::query()->where('sku', 'MENU-CHEESECAKE')->first();

        if ($branch === null || $cashier === null || $register === null || $latte === null) {
            return;
        }

        $session = CashRegisterSession::query()->firstOrCreate(
            [
                'cash_register_id' => $register->id,
                'session_date' => now()->toDateString(),
            ],
            [
                'user_id' => $cashier->id,
                'status' => 'open',
                'opening_balance' => 500,
                'expected_cash' => 500,
                'opened_at' => now()->subHours(2),
                'notes' => 'جرد يومي',
            ],
        );

        CashMovement::query()->firstOrCreate(
            [
                'session_id' => $session->id,
                'type' => CashMovementType::Deposit->value,
                'amount' => 500,
            ],
            ['notes' => 'رصيد افتتاحي'],
        );

        $completedOrder = Order::query()->firstOrCreate(
            ['order_number' => 'ORD-2026-001'],
            [
                'branch_id' => $branch->id,
                'type' => OrderType::Takeaway->value,
                'status' => OrderStatus::Completed->value,
                'customer_id' => $customer?->id,
                'user_id' => $cashier->id,
                'subtotal' => 34,
                'tax_amount' => 5.1,
                'total' => 39.1,
                'source' => 'pos',
            ],
        );

        OrderItem::query()->firstOrCreate(
            ['order_id' => $completedOrder->id, 'product_id' => $latte->id],
            [
                'quantity' => 1,
                'unit_price' => 18,
                'cost_price' => 6,
                'tax' => 2.7,
                'status' => OrderItemStatus::Ready->value,
            ],
        );

        if ($cappuccino !== null) {
            OrderItem::query()->firstOrCreate(
                ['order_id' => $completedOrder->id, 'product_id' => $cappuccino->id],
                [
                    'quantity' => 1,
                    'unit_price' => 16,
                    'cost_price' => 5,
                    'tax' => 2.4,
                    'status' => OrderItemStatus::Ready->value,
                ],
            );
        }

        $invoice = Invoice::query()->firstOrCreate(
            ['invoice_number' => 'INV-2026-001'],
            [
                'branch_id' => $branch->id,
                'order_id' => $completedOrder->id,
                'customer_id' => $customer?->id,
                'status' => InvoiceStatus::Paid->value,
                'subtotal' => 34,
                'tax' => 5.1,
                'total' => 39.1,
                'paid' => 39.1,
                'balance' => 0,
                'issued_at' => now()->subHour(),
                'user_id' => $cashier->id,
            ],
        );

        InvoiceItem::query()->firstOrCreate(
            ['invoice_id' => $invoice->id, 'product_id' => $latte->id],
            [
                'product_name' => $latte->name,
                'quantity' => 1,
                'unit_price' => 18,
                'cost_price' => 6,
                'tax' => 2.7,
                'total' => 20.7,
            ],
        );

        Payment::query()->firstOrCreate(
            ['invoice_id' => $invoice->id, 'method' => PaymentMethod::Cash->value],
            [
                'cash_register_session_id' => $session->id,
                'amount' => 39.1,
                'user_id' => $cashier->id,
                'paid_at' => now()->subHour(),
            ],
        );

        Order::query()->firstOrCreate(
            ['order_number' => 'ORD-2026-002'],
            [
                'branch_id' => $branch->id,
                'type' => OrderType::Delivery->value,
                'status' => OrderStatus::Preparing->value,
                'customer_id' => $customer?->id,
                'user_id' => $cashier->id,
                'subtotal' => 22,
                'delivery_fee' => 15,
                'tax_amount' => 3.3,
                'total' => 40.3,
                'source' => 'online',
                'delivery_address' => 'الرياض، حي النرجس، شارع 20',
            ],
        );

        if ($cheesecake !== null) {
            $deliveryOrder = Order::query()->where('order_number', 'ORD-2026-002')->first();

            if ($deliveryOrder !== null) {
                OrderItem::query()->firstOrCreate(
                    ['order_id' => $deliveryOrder->id, 'product_id' => $cheesecake->id],
                    [
                        'quantity' => 1,
                        'unit_price' => 22,
                        'cost_price' => 8,
                        'tax' => 3.3,
                        'status' => OrderItemStatus::Preparing->value,
                    ],
                );
            }
        }

        Order::query()->firstOrCreate(
            ['order_number' => 'ORD-2026-003'],
            [
                'branch_id' => $branch->id,
                'type' => OrderType::DineIn->value,
                'status' => OrderStatus::Pending->value,
                'user_id' => $cashier->id,
                'subtotal' => 12,
                'tax_amount' => 1.8,
                'total' => 13.8,
                'source' => 'pos',
            ],
        );
    }
}
