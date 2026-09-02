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

class InventoryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_report_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.reports.inventory'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/reports/inventory')
            ->has('balances')
            ->has('summary')
            ->has('warehouseBreakdown')
            ->has('filters')
            ->has('filterOptions'));
    }

    public function test_inventory_report_shows_stock_balances(): void
    {
        [$user, $warehouse, $product] = $this->seedStock();

        $response = $this->actingAs($user)->get(route('admin.reports.inventory'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('summary.materials_count', 1)
            ->where('summary.total_quantity', 25)
            ->has('balances.data', 1)
            ->where('balances.data.0.product_id', $product->id)
            ->where('balances.data.0.warehouse_id', $warehouse->id));
    }

    public function test_inventory_report_can_filter_low_stock(): void
    {
        [$user, $warehouse] = $this->seedStock(minStock: 30);

        $response = $this->actingAs($user)->get(route('admin.reports.inventory', [
            'stock_status' => 'low',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('summary.materials_count', 1)
            ->where('balances.data.0.stock_status', 'low'));
    }

    public function test_inventory_report_can_filter_by_warehouse(): void
    {
        [$user, $warehouse] = $this->seedStock();

        $response = $this->actingAs($user)->get(route('admin.reports.inventory', [
            'warehouse_id' => $warehouse->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('summary.materials_count', 1)
            ->where('filters.warehouse_id', (string) $warehouse->id));
    }

    /** @return array{0: User, 1: Warehouse, 2: Product} */
    private function seedStock(float $minStock = 5): array
    {
        $user = $this->adminUser();

        $warehouse = Warehouse::query()->create([
            'branch_id' => $user->branch_id,
            'name' => 'المستودع الرئيسي',
            'type' => WarehouseType::Main,
            'is_active' => true,
        ]);

        $unit = Unit::query()->create([
            'name' => 'كيلو',
            'abbreviation' => 'kg',
        ]);

        $product = Product::query()->create([
            'branch_id' => $user->branch_id,
            'name' => 'سكر',
            'sku' => 'SUG-001',
            'type' => ProductType::Raw,
            'unit_id' => $unit->id,
            'sale_price' => 0,
            'cost_price' => 4,
            'track_stock' => true,
            'min_stock' => $minStock,
            'is_active' => true,
        ]);

        StockBalance::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 25,
            'avg_cost' => 4,
        ]);

        return [$user, $warehouse, $product];
    }

    private function adminUser(): User
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $branch = Branch::query()->create([
            'name' => 'الفرع الرئيسي',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        return User::query()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@test.local',
            'password' => 'password',
            'branch_id' => $branch->id,
            'is_super_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
