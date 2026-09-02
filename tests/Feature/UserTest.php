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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->has('users')
            ->has('summary')
            ->has('filters')
            ->has('filterOptions'));
    }

    public function test_user_can_be_created(): void
    {
        $admin = $this->adminUser();
        $branch = Branch::query()->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'كاشير جديد',
            'username' => 'cashier1',
            'email' => 'cashier@test.local',
            'password' => 'password123',
            'branch_id' => $branch->id,
            'is_active' => true,
            'roles' => ['cashier'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $user = User::query()->where('username', 'cashier1')->firstOrFail();

        $this->assertSame(route('admin.users.show', $user), $response->headers->get('Location'));
        $this->assertTrue($user->hasRole('cashier'));
        $this->assertTrue($user->is_active);
    }

    public function test_user_can_be_updated(): void
    {
        $admin = $this->adminUser();

        $user = User::query()->create([
            'name' => 'موظف',
            'username' => 'staff1',
            'email' => 'staff@test.local',
            'password' => 'password',
            'branch_id' => $admin->branch_id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'موظف محدّث',
            'username' => 'staff1',
            'email' => 'staff-updated@test.local',
            'is_active' => false,
            'roles' => ['manager'],
        ]);

        $response->assertRedirect(route('admin.users.show', $user));
        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('موظف محدّث', $user->name);
        $this->assertFalse($user->is_active);
        $this->assertTrue($user->hasRole('manager'));
    }

    public function test_user_cannot_delete_self(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect();
        $this->assertModelExists($admin);
    }

    public function test_user_with_invoices_cannot_be_deleted(): void
    {
        $admin = $this->adminUser();
        $cashier = $this->createCashierWithSale($admin);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $cashier));

        $response->assertRedirect();
        $this->assertModelExists($cashier);
    }

    public function test_users_index_can_filter_by_role(): void
    {
        $admin = $this->adminUser();

        $cashier = User::query()->create([
            'name' => 'كاشير',
            'username' => 'cashier-filter',
            'password' => 'password',
            'branch_id' => $admin->branch_id,
            'is_active' => true,
        ]);
        $cashier->assignRole(Role::query()->where('name', 'cashier')->firstOrFail());

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'role' => 'cashier',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.role', 'cashier')
            ->has('users.data', 1)
            ->where('users.data.0.id', $cashier->id));
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

    private function createCashierWithSale(User $admin): User
    {
        $cashier = User::query()->create([
            'name' => 'كاشير',
            'username' => 'cashier-sale',
            'password' => 'password',
            'branch_id' => $admin->branch_id,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::query()->create([
            'branch_id' => $admin->branch_id,
            'name' => 'المستودع الرئيسي',
            'type' => WarehouseType::Main,
            'is_active' => true,
        ]);

        CashRegister::query()->create([
            'branch_id' => $admin->branch_id,
            'name' => 'الصندوق الرئيسي',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'branch_id' => $admin->branch_id,
            'name' => 'قهوة',
            'type' => ProductType::Finished,
            'sale_price' => 18,
            'cost_price' => 6,
            'track_stock' => false,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'branch_id' => $admin->branch_id,
            'order_number' => 'ORD-USR-001',
            'type' => OrderType::Takeaway,
            'status' => OrderStatus::Confirmed,
            'user_id' => $cashier->id,
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
        $session = $register->getOrCreateDailySession($registerModel, $cashier);

        app(CompleteSaleAction::class)->execute(
            order: $order->fresh(['items.product']),
            warehouse: $warehouse,
            payments: [['method' => 'cash', 'amount' => 18]],
            session: $session,
            user: $cashier,
        );

        return $cashier;
    }
}
