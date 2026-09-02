<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_entries_index_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.journal-entries.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/journal-entries/index')
            ->has('entries')
            ->has('summary'));
    }

    public function test_manual_journal_entry_can_be_created(): void
    {
        $user = $this->adminUser();

        $cashAccount = ChartOfAccount::query()->where('code', '1100')->firstOrFail();
        $salesAccount = ChartOfAccount::query()->where('code', '4100')->firstOrFail();

        $response = $this->actingAs($user)->post(route('admin.journal-entries.store'), [
            'date' => now()->toDateString(),
            'description' => 'قيد اختبار',
            'lines' => [
                [
                    'account_id' => $cashAccount->id,
                    'debit' => 500,
                    'credit' => 0,
                    'description' => 'نقدية',
                ],
                [
                    'account_id' => $salesAccount->id,
                    'debit' => 0,
                    'credit' => 500,
                    'description' => 'مبيعات',
                ],
            ],
        ]);

        $entry = JournalEntry::query()->where('description', 'قيد اختبار')->firstOrFail();

        $response->assertRedirect(route('admin.journal-entries.show', $entry));

        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->id,
            'description' => 'قيد اختبار',
            'reference_type' => null,
            'branch_id' => $user->branch_id,
        ]);

        $this->assertDatabaseCount('journal_entry_lines', 2);
    }

    public function test_unbalanced_journal_entry_is_rejected(): void
    {
        $user = $this->adminUser();

        $cashAccount = ChartOfAccount::query()->where('code', '1100')->firstOrFail();
        $salesAccount = ChartOfAccount::query()->where('code', '4100')->firstOrFail();

        $response = $this->actingAs($user)->post(route('admin.journal-entries.store'), [
            'date' => now()->toDateString(),
            'description' => 'قيد غير متوازن',
            'lines' => [
                [
                    'account_id' => $cashAccount->id,
                    'debit' => 500,
                    'credit' => 0,
                ],
                [
                    'account_id' => $salesAccount->id,
                    'debit' => 0,
                    'credit' => 300,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('lines');
        $this->assertDatabaseMissing('journal_entries', ['description' => 'قيد غير متوازن']);
    }

    public function test_system_journal_entry_cannot_be_deleted(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)->post(route('admin.expenses.store'), [
            'category' => 'تشغيل',
            'amount' => 100,
            'payment_method' => PaymentMethod::Card->value,
            'description' => 'مصروف',
            'expense_date' => now()->toDateString(),
        ]);

        $expense = Expense::query()->firstOrFail();
        $entry = JournalEntry::query()
            ->where('reference_type', $expense->getMorphClass())
            ->where('reference_id', $expense->id)
            ->firstOrFail();

        $response = $this->actingAs($user)->delete(route('admin.journal-entries.destroy', $entry));

        $response->assertRedirect();
        $this->assertDatabaseHas('journal_entries', ['id' => $entry->id]);
    }

    public function test_manual_journal_entry_can_be_deleted(): void
    {
        $user = $this->adminUser();

        $cashAccount = ChartOfAccount::query()->where('code', '1100')->firstOrFail();
        $salesAccount = ChartOfAccount::query()->where('code', '4100')->firstOrFail();

        $this->actingAs($user)->post(route('admin.journal-entries.store'), [
            'date' => now()->toDateString(),
            'description' => 'قيد للحذف',
            'lines' => [
                ['account_id' => $cashAccount->id, 'debit' => 200, 'credit' => 0],
                ['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 200],
            ],
        ]);

        $entry = JournalEntry::query()->where('description', 'قيد للحذف')->firstOrFail();

        $response = $this->actingAs($user)->delete(route('admin.journal-entries.destroy', $entry));

        $response->assertRedirect(route('admin.journal-entries.index'));
        $this->assertDatabaseMissing('journal_entries', ['id' => $entry->id]);
    }

    public function test_show_page_displays_entry_details(): void
    {
        $user = $this->adminUser();

        $cashAccount = ChartOfAccount::query()->where('code', '1100')->firstOrFail();
        $salesAccount = ChartOfAccount::query()->where('code', '4100')->firstOrFail();

        $this->actingAs($user)->post(route('admin.journal-entries.store'), [
            'date' => now()->toDateString(),
            'description' => 'قيد عرض',
            'lines' => [
                ['account_id' => $cashAccount->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $entry = JournalEntry::query()->where('description', 'قيد عرض')->firstOrFail();

        $response = $this->actingAs($user)->get(route('admin.journal-entries.show', $entry));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/journal-entries/show')
            ->has('entry')
            ->where('entry.description', 'قيد عرض')
            ->has('entry.lines', 2));
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
