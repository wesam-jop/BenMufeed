<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PosDeliveryOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_accept_pending_online_delivery_order(): void
    {
        [$user, $order] = $this->pendingDeliveryOrder();

        $response = $this->actingAs($user)->postJson(route('pos.delivery.accept', $order));

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Confirmed->value,
        ]);
    }

    public function test_cashier_can_reject_pending_online_delivery_order(): void
    {
        [$user, $order] = $this->pendingDeliveryOrder();

        $response = $this->actingAs($user)->postJson(route('pos.delivery.reject', $order), [
            'reason' => 'خارج نطاق التوصيل',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Rejected->value,
            'rejection_reason' => 'خارج نطاق التوصيل',
        ]);
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function pendingDeliveryOrder(): array
    {
        $branch = Branch::query()->create([
            'name' => 'الفرع الرئيسي',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]);

        foreach (['pos.access', 'orders.accept_delivery', 'orders.reject_delivery'] as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        $customer = Customer::query()->create([
            'phone' => '0502222222',
            'name' => 'زبون توصيل',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'ORD-TEST-001',
            'type' => OrderType::Delivery,
            'status' => OrderStatus::Pending,
            'customer_id' => $customer->id,
            'source' => 'online',
            'delivery_address' => 'الرياض — حي النخيل',
            'subtotal' => 50,
            'total' => 50,
        ]);

        return [$user, $order];
    }
}
