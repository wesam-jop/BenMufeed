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
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_report_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.reports.products'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/reports/products')
            ->has('items')
            ->has('summary')
            ->has('categoryBreakdown')
            ->has('filters')
            ->has('filterOptions'));
    }

    public function test_products_report_shows_sale_data(): void
    {
        $user = $this->adminUser();
        $product = $this->createSale($user);

        $response = $this->actingAs($user)->get(route('admin.reports.products', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('summary.products_count', 1)
            ->where('summary.total_revenue', 18)
            ->has('items.data', 1)
            ->where('items.data.0.product_id', $product->id)
            ->where('items.data.0.quantity', 1));
    }

    public function test_products_report_can_search_by_name(): void
    {
        $user = $this->adminUser();
        $this->createSale($user);

        $response = $this->actingAs($user)->get(route('admin.reports.products', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
            'search' => 'قهوة',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('summary.products_count', 1)
            ->where('filters.search', 'قهوة'));
    }

    private function adminUser(): User
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class, ChartOfAccountSeeder::class]);

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

    private function createSale(User $user): Product
    {
        $warehouse = Warehouse::query()->create([
            'branch_id' => $user->branch_id,
            'name' => 'المستودع الرئيسي',
            'type' => WarehouseType::Main,
            'is_active' => true,
        ]);

        CashRegister::query()->create([
            'branch_id' => $user->branch_id,
            'name' => 'الصندوق الرئيسي',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'branch_id' => $user->branch_id,
            'name' => 'قهوة',
            'type' => ProductType::Finished,
            'sale_price' => 18,
            'cost_price' => 6,
            'track_stock' => false,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'branch_id' => $user->branch_id,
            'order_number' => 'ORD-PRD-001',
            'type' => OrderType::Takeaway,
            'status' => OrderStatus::Confirmed,
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

        $register = app(\App\Services\Accounting\CashRegisterService::class);
        $registerModel = CashRegister::query()->firstOrFail();
        $session = $register->getOrCreateDailySession($registerModel, $user);

        app(CompleteSaleAction::class)->execute(
            order: $order->fresh(['items.product']),
            warehouse: $warehouse,
            payments: [['method' => 'cash', 'amount' => 18]],
            session: $session,
            user: $user,
        );

        return $product;
    }
}
