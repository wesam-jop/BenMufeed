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

class CashRegisterSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_register_sessions_index_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.cash-register-sessions.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/cash-register-sessions/index')
            ->has('cashRegisterSessions')
            ->has('summary'));
    }

    public function test_session_can_be_opened_and_closed(): void
    {
        $user = $this->adminUser();

        $register = CashRegister::query()->create([
            'branch_id' => $user->branch_id,
            'name' => 'الصندوق الرئيسي',
            'is_active' => true,
        ]);

        $open = $this->actingAs($user)->post(route('admin.cash-register-sessions.store'), [
            'cash_register_id' => $register->id,
            'opening_balance' => 200,
            'notes' => 'افتتاح',
        ]);

        $session = CashRegisterSession::query()->firstOrFail();

        $open->assertRedirect(route('admin.cash-register-sessions.show', $session));
        $session->refresh();
        $this->assertSame('open', $session->status);
        $this->assertSame(200.0, (float) $session->opening_balance);
        $this->assertSame(200.0, (float) $session->expected_cash);

        $close = $this->actingAs($user)->post(route('admin.cash-register-sessions.close', $session), [
            'actual_cash' => 195,
            'notes' => 'نقص بسيط',
        ]);

        $close->assertRedirect(route('admin.cash-register-sessions.show', $session));

        $session->refresh();
        $this->assertSame('closed', $session->status);
        $this->assertSame(195.0, (float) $session->actual_cash);
        $this->assertSame(-5.0, (float) $session->difference);
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
