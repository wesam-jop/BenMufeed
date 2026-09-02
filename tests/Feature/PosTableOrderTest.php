<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ProductType;
use App\Enums\TableStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\TableSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PosTableOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_table_returns_existing_order_items(): void
    {
        [$user, $table, $product] = $this->tableContext();

        $session = TableSession::query()->create([
            'table_id' => $table->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $order = Order::query()->create([
            'branch_id' => $table->branch_id,
            'order_number' => 'ORD-TABLE-001',
            'type' => OrderType::DineIn,
            'status' => OrderStatus::Pending,
            'table_session_id' => $session->id,
            'user_id' => $user->id,
            'source' => 'pos',
            'subtotal' => 18,
            'total' => 18,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 18,
            'cost_price' => 6,
            'tax' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->getJson(route('pos.tables.order', $table));

        $response->assertOk();
        $response->assertJsonPath('order.id', $order->id);
        $response->assertJsonCount(1, 'order.items');
    }

    public function test_table_order_item_can_be_removed(): void
    {
        [$user, $table, $product] = $this->tableContext();

        $order = Order::query()->create([
            'branch_id' => $table->branch_id,
            'order_number' => 'ORD-TABLE-002',
            'type' => OrderType::DineIn,
            'status' => OrderStatus::Pending,
            'user_id' => $user->id,
            'source' => 'pos',
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 18,
            'cost_price' => 6,
            'tax' => 0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->deleteJson(
            route('pos.orders.items.destroy', [$order, $item]),
        );

        $response->assertOk();
        $this->assertDatabaseMissing('order_items', ['id' => $item->id]);
    }

    /**
     * @return array{0: User, 1: RestaurantTable, 2: Product}
     */
    private function tableContext(): array
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

        Permission::query()->firstOrCreate(['name' => 'pos.access', 'guard_name' => 'web']);
        $user->givePermissionTo('pos.access');

        $table = RestaurantTable::query()->create([
            'branch_id' => $branch->id,
            'name' => 'طاولة 1',
            'capacity' => 4,
            'zone' => 'داخلي',
            'qr_token' => 'test-qr-token',
            'access_code' => '0001',
            'status' => TableStatus::Free,
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'branch_id' => $branch->id,
            'name' => 'لاتيه',
            'sku' => 'TEST-LATTE',
            'type' => ProductType::Finished,
            'sale_price' => 18,
            'cost_price' => 6,
            'is_active' => true,
        ]);

        return [$user, $table, $product];
    }
}
