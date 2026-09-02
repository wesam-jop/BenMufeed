<?php

namespace Tests\Feature;

use App\Actions\CompleteSaleAction;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ProductType;
use App\Enums\WarehouseType;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomersReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_report_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.reports.customers'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/reports/customers')
            ->has('customers')
            ->has('summary')
            ->has('topCustomers')
            ->has('filters')
            ->has('filterOptions'));
    }

    public function test_customers_report_shows_customer_sales_data(): void
    {
        $user = $this->adminUser();
        $customer = $this->createSaleWithCustomer($user);

        $response = $this->actingAs($user)->get(route('admin.reports.customers', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('summary.customers_count', 1)
            ->where('summary.total_revenue', 18)
            ->has('topCustomers', 1)
            ->has('customers.data', 1)
            ->where('customers.data.0.id', $customer->id)
            ->where('customers.data.0.total_spent', 18));
    }

    public function test_customers_report_can_filter_by_search(): void
    {
        $user = $this->adminUser();
        $customer = $this->createSaleWithCustomer($user);

        $response = $this->actingAs($user)->get(route('admin.reports.customers', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
            'search' => $customer->phone,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('summary.customers_count', 1)
            ->where('filters.search', $customer->phone));
    }

    public function test_customers_report_can_show_customers_without_sales(): void
    {
        $user = $this->adminUser();

        $customer = Customer::query()->create([
            'phone' => '0501111111',
            'name' => 'عميل بدون مشتريات',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('admin.reports.customers', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
            'activity' => 'no_sales',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('summary.customers_count', 1)
            ->where('customers.data.0.id', $customer->id));
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

    private function createSaleWithCustomer(User $user): Customer
    {
        $customer = Customer::query()->create([
            'phone' => '0503333333',
            'name' => 'عميل VIP',
            'loyalty_points' => 120,
            'is_active' => true,
        ]);

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
            'order_number' => 'ORD-CUS-001',
            'type' => OrderType::Takeaway,
            'status' => OrderStatus::Confirmed,
            'customer_id' => $customer->id,
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

        return $customer;
    }
}
