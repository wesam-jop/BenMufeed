<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\Expense;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.expenses.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/expenses/index')
            ->has('expenses')
            ->has('summary'));
    }

    public function test_expense_can_be_created_and_posts_accounting_entry(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.expenses.store'), [
            'category' => 'تشغيل',
            'amount' => 150,
            'payment_method' => PaymentMethod::Card->value,
            'description' => 'صيانة',
            'expense_date' => now()->toDateString(),
        ]);

        $expense = Expense::query()->firstOrFail();

        $response->assertRedirect(route('admin.expenses.show', $expense));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'category' => 'تشغيل',
            'amount' => 150,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => $expense->getMorphClass(),
            'reference_id' => $expense->id,
        ]);
    }

    public function test_cash_expense_deducts_from_open_register_session(): void
    {
        $user = $this->adminUser();

        $register = CashRegister::query()->create([
            'branch_id' => $user->branch_id,
            'name' => 'الصندوق الرئيسي',
            'is_active' => true,
        ]);

        $session = CashRegisterSession::query()->create([
            'cash_register_id' => $register->id,
            'user_id' => $user->id,
            'session_date' => now()->toDateString(),
            'status' => 'open',
            'opening_balance' => 500,
            'expected_cash' => 500,
            'opened_at' => now(),
        ]);

        $this->actingAs($user)->post(route('admin.expenses.store'), [
            'category' => 'مرافق',
            'amount' => 50,
            'payment_method' => PaymentMethod::Cash->value,
            'description' => 'مياه',
            'expense_date' => now()->toDateString(),
        ]);

        $expense = Expense::query()->firstOrFail();

        $this->assertSame($session->id, $expense->cash_register_session_id);
        $this->assertDatabaseHas('cash_movements', [
            'session_id' => $session->id,
            'type' => 'expense',
            'amount' => 50,
        ]);

        $session->refresh();
        $this->assertSame(450.0, (float) $session->expected_cash);
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
}
