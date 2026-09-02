<?php

namespace Tests\Feature;

use App\Actions\CompleteSaleAction;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ProductType;
use App\Enums\WarehouseType;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PosCompleteOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_pos_table_order_can_be_completed(): void
    {
        $this->seed(ChartOfAccountSeeder::class);

        [$user, $warehouse, $product] = $this->posContext();

        CashRegister::query()->create([
            'branch_id' => $user->branch_id,
            'name' => 'الصندوق الرئيسي',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'branch_id' => $user->branch_id,
            'order_number' => 'ORD-COMPLETE-001',
            'type' => OrderType::DineIn,
            'status' => OrderStatus::Pending,
            'user_id' => $user->id,
            'source' => 'pos',
            'subtotal' => 18,
            'tax_amount' => 0,
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

        $response = $this->actingAs($user)->postJson(route('pos.orders.complete', $order), [
            'warehouse_id' => $warehouse->id,
            'payments' => [
                ['method' => 'cash', 'amount' => 18],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['invoice']);
    }

    /**
     * @return array{0: User, 1: Warehouse, 2: Product}
     */
    private function posContext(): array
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

        foreach (['pos.access', 'pos.open_session', 'pos.close_session'] as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        $warehouse = Warehouse::query()->create([
            'branch_id' => $branch->id,
            'name' => 'المستودع الرئيسي',
            'type' => WarehouseType::Main,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'branch_id' => $branch->id,
            'name' => 'لاتيه',
            'sku' => 'TEST-LATTE',
            'type' => ProductType::Finished,
            'sale_price' => 18,
            'cost_price' => 6,
            'track_stock' => false,
            'is_active' => true,
        ]);

        return [$user, $warehouse, $product];
    }
}
