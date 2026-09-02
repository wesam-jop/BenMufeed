<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_of_accounts_index_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.chart-of-accounts.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/chart-of-accounts/index')
            ->has('accounts')
            ->has('summary'));
    }

    public function test_account_can_be_created(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.chart-of-accounts.store'), [
            'code' => '6200',
            'name' => 'مصروفات إيجار',
            'type' => AccountType::Expense->value,
            'is_active' => true,
        ]);

        $account = ChartOfAccount::query()->where('code', '6200')->firstOrFail();

        $response->assertRedirect(route('admin.chart-of-accounts.show', $account));

        $this->assertDatabaseHas('chart_of_accounts', [
            'code' => '6200',
            'name' => 'مصروفات إيجار',
            'type' => AccountType::Expense->value,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    public function test_system_account_cannot_be_deleted(): void
    {
        $user = $this->adminUser();

        $account = ChartOfAccount::query()->where('code', '1100')->firstOrFail();

        $response = $this->actingAs($user)->delete(route('admin.chart-of-accounts.destroy', $account));

        $response->assertRedirect();
        $this->assertDatabaseHas('chart_of_accounts', ['id' => $account->id]);
    }

    public function test_custom_account_can_be_deleted(): void
    {
        $user = $this->adminUser();

        $account = ChartOfAccount::query()->create([
            'code' => '9999',
            'name' => 'حساب تجريبي',
            'type' => AccountType::Expense,
            'is_system' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('admin.chart-of-accounts.destroy', $account));

        $response->assertRedirect(route('admin.chart-of-accounts.index'));
        $this->assertDatabaseMissing('chart_of_accounts', ['id' => $account->id]);
    }

    public function test_show_page_displays_account_details(): void
    {
        $user = $this->adminUser();

        $account = ChartOfAccount::query()->where('code', '6100')->firstOrFail();

        $response = $this->actingAs($user)->get(route('admin.chart-of-accounts.show', $account));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/chart-of-accounts/show')
            ->has('account')
            ->has('recentLines')
            ->where('account.code', '6100'));
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
