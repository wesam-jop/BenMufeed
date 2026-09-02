<?php

namespace Database\Seeders;

use App\Enums\JournalEntryStatus;
use App\Enums\PaymentMethod;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Database\Seeders\Concerns\InteractsWithMainBranch;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    use InteractsWithMainBranch;

    public function run(): void
    {
        $branch = $this->mainBranch();
        $user = $this->adminUser();
        $cashAccount = ChartOfAccount::query()->where('code', '1100')->first();
        $salesAccount = ChartOfAccount::query()->where('code', '4100')->first();
        $expenseAccount = ChartOfAccount::query()->where('code', '6100')->first();

        if ($branch === null || $user === null || $cashAccount === null || $salesAccount === null) {
            return;
        }

        $period = AccountingPeriod::query()->firstOrCreate(
            ['branch_id' => $branch->id, 'name' => '2026-01'],
            [
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 'open',
            ],
        );

        $entry = JournalEntry::query()->firstOrCreate(
            ['entry_number' => 'JE-2026-001'],
            [
                'branch_id' => $branch->id,
                'date' => now()->toDateString(),
                'description' => 'قيد افتتاحي للمبيعات',
                'status' => JournalEntryStatus::Posted->value,
                'period_id' => $period->id,
                'user_id' => $user->id,
            ],
        );

        JournalEntryLine::query()->firstOrCreate(
            ['journal_entry_id' => $entry->id, 'account_id' => $cashAccount->id, 'debit' => 1000],
            ['credit' => 0, 'description' => 'نقدية'],
        );

        JournalEntryLine::query()->firstOrCreate(
            ['journal_entry_id' => $entry->id, 'account_id' => $salesAccount->id, 'credit' => 1000],
            ['debit' => 0, 'description' => 'إيرادات مبيعات'],
        );

        Expense::query()->firstOrCreate(
            [
                'branch_id' => $branch->id,
                'category' => 'تشغيل',
                'amount' => 350,
                'expense_date' => now()->subDays(2)->toDateString(),
            ],
            [
                'account_id' => $expenseAccount?->id,
                'payment_method' => PaymentMethod::Cash->value,
                'description' => 'فاتورة كهرباء الشهر',
                'user_id' => $user->id,
            ],
        );
    }
}
