<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_be_created_with_name_only(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.suppliers.store'), [
            'name' => 'مورد سريع',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $supplier = Supplier::query()->where('name', 'مورد سريع')->firstOrFail();

        $this->assertSame(route('admin.suppliers.show', $supplier), $response->headers->get('Location'));

        $this->assertDatabaseHas('suppliers', [
            'name' => 'مورد سريع',
            'contact_person' => null,
            'phone' => null,
            'email' => null,
            'is_active' => true,
        ]);
    }

    public function test_supplier_can_be_created_with_optional_fields(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.suppliers.store'), [
            'name' => 'مورد كامل',
            'contact_person' => 'أحمد',
            'phone' => '0501234567',
            'email' => 'supplier@test.local',
            'address' => 'الرياض',
            'tax_number' => '300123456789003',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $supplier = Supplier::query()->where('name', 'مورد كامل')->firstOrFail();

        $this->assertSame(route('admin.suppliers.show', $supplier), $response->headers->get('Location'));

        $this->assertDatabaseHas('suppliers', [
            'name' => 'مورد كامل',
            'contact_person' => 'أحمد',
            'phone' => '0501234567',
            'email' => 'supplier@test.local',
        ]);
    }

    public function test_supplier_name_is_required(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.suppliers.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('suppliers', 0);
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
