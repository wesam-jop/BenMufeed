<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UnifiedLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_unified_login_redirects_admin_to_dashboard(): void
    {
        $user = User::factory()->create();

        Permission::query()->firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);
        $user->givePermissionTo('dashboard.view');

        $response = $this->post(route('auth.login.store'), [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated('web');
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_unified_login_redirects_cashier_to_pos(): void
    {
        $user = User::factory()->create();

        Permission::query()->firstOrCreate(['name' => 'pos.access', 'guard_name' => 'web']);
        $user->givePermissionTo('pos.access');

        $response = $this->post(route('auth.login.store'), [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated('web');
        $response->assertRedirect(route('pos.terminal', absolute: false));
    }

    public function test_unified_login_redirects_customer_to_customer_home(): void
    {
        Customer::query()->create([
            'phone' => '0500000001',
            'name' => 'Test Customer',
            'password' => 'password',
            'is_active' => true,
        ]);

        $response = $this->post(route('auth.login.store'), [
            'login' => '0500000001',
            'password' => 'password',
        ]);

        $this->assertAuthenticated('customer');
        $response->assertRedirect(route('customer.home', absolute: false));
    }

    public function test_fortify_login_view_redirects_to_auth_login(): void
    {
        $this->get(route('login'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_legacy_customer_login_redirects_to_auth_login(): void
    {
        $this->get('/customer/login')
            ->assertRedirect('/auth/login');
    }

    public function test_guest_visiting_admin_is_redirected_to_auth_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_customer_cannot_access_admin_area(): void
    {
        $customer = Customer::query()->create([
            'phone' => '0500000002',
            'name' => 'Blocked Customer',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('customer.home'));
    }

    public function test_staff_cannot_access_customer_portal(): void
    {
        $branch = Branch::query()->create([
            'name' => 'الفرع الرئيسي',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'is_super_admin' => true,
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($user, 'web')
            ->get(route('customer.home'))
            ->assertRedirect(route('admin.dashboard', absolute: false));
    }
}
