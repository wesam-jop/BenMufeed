<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_loss_report_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.accounting.reports.profit-loss'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/accounting/reports/profit-loss')
            ->has('report')
            ->has('report.summary')
            ->has('report.revenue')
            ->has('report.cogs')
            ->has('report.expenses')
            ->has('filters'));
    }

    public function test_balance_sheet_report_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.accounting.reports.balance-sheet'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/accounting/reports/balance-sheet')
            ->has('report')
            ->has('report.sections')
            ->has('report.summary')
            ->where('report.summary.is_balanced', true)
            ->has('filters'));
    }

    public function test_balance_sheet_includes_retained_earnings_from_operations(): void
    {
        $user = $this->adminUser();

        $cashAccount = \App\Models\ChartOfAccount::query()->where('code', '1100')->firstOrFail();
        $salesAccount = \App\Models\ChartOfAccount::query()->where('code', '4100')->firstOrFail();

        $this->actingAs($user)->post(route('admin.journal-entries.store'), [
            'date' => now()->toDateString(),
            'description' => 'مبيعات للميزانية',
            'lines' => [
                ['account_id' => $cashAccount->id, 'debit' => 800, 'credit' => 0],
                ['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 800],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('admin.accounting.reports.balance-sheet'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('report.summary.net_income', 800)
            ->where('report.summary.total_assets', 800)
            ->where('report.summary.total_equity', 800)
            ->where('report.summary.is_balanced', true));
    }

    public function test_trial_balance_report_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.accounting.reports.trial-balance'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/accounting/reports/trial-balance')
            ->has('report')
            ->has('report.lines')
            ->has('report.sections')
            ->has('report.summary')
            ->where('report.summary.is_balanced', true)
            ->has('filters'));
    }

    public function test_trial_balance_shows_balanced_journal_entry(): void
    {
        $user = $this->adminUser();

        $cashAccount = \App\Models\ChartOfAccount::query()->where('code', '1100')->firstOrFail();
        $salesAccount = \App\Models\ChartOfAccount::query()->where('code', '4100')->firstOrFail();

        $this->actingAs($user)->post(route('admin.journal-entries.store'), [
            'date' => now()->toDateString(),
            'description' => 'قيد لميزان المراجعة',
            'lines' => [
                ['account_id' => $cashAccount->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('admin.accounting.reports.trial-balance'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('report.summary.accounts_count', 2)
            ->where('report.summary.total_debit', 500)
            ->where('report.summary.total_credit', 500)
            ->where('report.summary.is_balanced', true));
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
