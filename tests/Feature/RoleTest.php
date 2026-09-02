<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_index_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.roles.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/roles/index')
            ->has('roles')
            ->has('summary')
            ->has('filters'));
    }

    public function test_role_can_be_created_with_permissions(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.roles.store'), [
            'name' => 'supervisor',
            'permissions' => ['products.view', 'inventory.view', 'sales.view'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $role = Role::query()->where('name', 'supervisor')->firstOrFail();

        $this->assertSame(route('admin.roles.show', $role), $response->headers->get('Location'));
        $this->assertTrue($role->hasPermissionTo('products.view'));
        $this->assertTrue($role->hasPermissionTo('sales.view'));
    }

    public function test_role_can_be_updated(): void
    {
        $user = $this->adminUser();

        $role = Role::query()->create([
            'name' => 'custom-role',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($user)->put(route('admin.roles.update', $role), [
            'name' => 'custom-role',
            'permissions' => ['orders.view', 'orders.update_status'],
        ]);

        $response->assertRedirect(route('admin.roles.show', $role));
        $response->assertSessionHasNoErrors();

        $role->refresh();

        $this->assertTrue($role->hasPermissionTo('orders.view'));
        $this->assertTrue($role->hasPermissionTo('orders.update_status'));
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $user = $this->adminUser();
        $role = Role::query()->where('name', 'cashier')->firstOrFail();

        $response = $this->actingAs($user)->delete(route('admin.roles.destroy', $role));

        $response->assertRedirect();
        $this->assertModelExists($role);
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $user = $this->adminUser();

        $role = Role::query()->create([
            'name' => 'temp-role',
            'guard_name' => 'web',
        ]);

        $assignedUser = User::query()->create([
            'name' => 'موظف',
            'username' => 'employee1',
            'password' => 'password',
            'branch_id' => $user->branch_id,
            'is_active' => true,
        ]);
        $assignedUser->assignRole($role);

        $response = $this->actingAs($user)->delete(route('admin.roles.destroy', $role));

        $response->assertRedirect();
        $this->assertModelExists($role);
    }

    public function test_system_role_name_cannot_be_changed(): void
    {
        $user = $this->adminUser();
        $role = Role::query()->where('name', 'manager')->firstOrFail();

        $response = $this->actingAs($user)->put(route('admin.roles.update', $role), [
            'name' => 'branch-manager',
            'permissions' => ['dashboard.view'],
        ]);

        $response->assertRedirect();
        $this->assertSame('manager', $role->fresh()->name);
    }

    private function adminUser(): User
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        Branch::query()->create([
            'name' => 'الفرع الرئيسي',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        return User::query()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@test.local',
            'password' => 'password',
            'is_super_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
