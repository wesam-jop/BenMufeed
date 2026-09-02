<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\WarehouseType;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_index_lists_warehouses_with_summary(): void
    {
        [$user, $warehouse] = $this->seedWarehouse();

        $response = $this->actingAs($user)->get(route('admin.warehouses.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/warehouses/index')
            ->has('warehouses.data', 1)
            ->where('warehouses.data.0.name', $warehouse->name)
            ->has('summary'));
    }

    public function test_warehouse_can_be_created(): void
    {
        [$user] = $this->seedWarehouse();

        $response = $this->actingAs($user)->post(route('admin.warehouses.store'), [
            'name' => 'مستودع البار',
            'type' => WarehouseType::Retail->value,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::query()->where('name', 'مستودع البار')->first();

        $response->assertRedirect(route('admin.warehouses.show', $warehouse));
        $this->assertDatabaseHas('warehouses', [
            'name' => 'مستودع البار',
            'type' => WarehouseType::Retail->value,
            'is_active' => true,
        ]);
    }

    public function test_warehouse_show_displays_stock(): void
    {
        [$user, $warehouse, $product] = $this->seedWarehouse(withProduct: true);

        StockBalance::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 15,
            'avg_cost' => 10,
        ]);

        $response = $this->actingAs($user)->get(route('admin.warehouses.show', $warehouse));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/warehouses/show')
            ->where('warehouse.name', $warehouse->name)
            ->has('warehouse.materials', 1)
            ->where('warehouse.summary.total_value', 150));
    }

    public function test_warehouse_with_stock_cannot_be_deleted(): void
    {
        [$user, $warehouse, $product] = $this->seedWarehouse(withProduct: true);

        StockBalance::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'avg_cost' => 10,
        ]);

        $response = $this->actingAs($user)->delete(route('admin.warehouses.destroy', $warehouse));

        $response->assertRedirect();
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
    }

    /** @return array{0: User, 1: Warehouse, 2?: Product} */
    private function seedWarehouse(bool $withProduct = false): array
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $branch = Branch::query()->create([
            'name' => 'الفرع الرئيسي',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@test.local',
            'password' => 'password',
            'branch_id' => $branch->id,
            'is_super_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $warehouse = Warehouse::query()->create([
            'branch_id' => $branch->id,
            'name' => 'المستودع الرئيسي',
            'type' => WarehouseType::Main,
            'is_active' => true,
        ]);

        if (! $withProduct) {
            return [$user, $warehouse];
        }

        $unit = Unit::query()->create([
            'name' => 'كيلو',
            'abbreviation' => 'kg',
        ]);

        $product = Product::query()->create([
            'branch_id' => $branch->id,
            'name' => 'سكر',
            'sku' => 'RAW-SUGAR',
            'type' => ProductType::Raw,
            'track_stock' => true,
            'is_active' => true,
            'cost_price' => 10,
            'sale_price' => 0,
            'unit_id' => $unit->id,
        ]);

        return [$user, $warehouse, $product];
    }
}
