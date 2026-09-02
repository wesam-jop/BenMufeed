<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_register_can_be_created_with_branch(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.cash-registers.store'), [
            'name' => 'صندوق البار',
            'is_active' => true,
        ]);

        $register = CashRegister::query()->where('name', 'صندوق البار')->firstOrFail();

        $response->assertRedirect(route('admin.cash-registers.show', $register));

        $this->assertDatabaseHas('cash_registers', [
            'name' => 'صندوق البار',
            'branch_id' => $user->branch_id,
            'is_active' => true,
        ]);
    }

    public function test_cash_register_show_lists_recent_sessions(): void
    {
        $user = $this->adminUser();

        $register = CashRegister::query()->create([
            'branch_id' => $user->branch_id,
            'name' => 'الصندوق الرئيسي',
            'is_active' => true,
        ]);

        CashRegisterSession::query()->create([
            'cash_register_id' => $register->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'status' => 'open',
            'opening_balance' => 100,
            'expected_cash' => 100,
            'opened_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('admin.cash-registers.show', $register));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/cash-registers/show')
            ->where('cashRegister.name', 'الصندوق الرئيسي')
            ->has('cashRegister.sessions', 1)
            ->where('cashRegister.sessions.0.status', 'open'));
    }

    public function test_cash_register_with_sessions_cannot_be_deleted(): void
    {
        $user = $this->adminUser();

        $register = CashRegister::query()->create([
            'branch_id' => $user->branch_id,
            'name' => 'صندوق قديم',
            'is_active' => true,
        ]);

        CashRegisterSession::query()->create([
            'cash_register_id' => $register->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'status' => 'closed',
            'opening_balance' => 0,
            'expected_cash' => 50,
            'actual_cash' => 50,
            'difference' => 0,
            'opened_at' => now()->subHour(),
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($user)->delete(route('admin.cash-registers.destroy', $register));

        $response->assertRedirect();
        $this->assertDatabaseHas('cash_registers', ['id' => $register->id]);
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
