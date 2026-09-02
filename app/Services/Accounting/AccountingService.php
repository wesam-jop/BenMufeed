<?php

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Enums\JournalEntryStatus;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PurchaseInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AccountingService
{
    public function __construct(
        private NumberGeneratorService $numberGenerator,
    ) {}

    public function postSaleEntry(Invoice $invoice, ?User $user = null): JournalEntry
    {
        $invoice->loadMissing('items');

        $total = (float) $invoice->total;
        $tax = (float) $invoice->tax;
        $netSales = $total - $tax;
        $cogs = $invoice->items->sum(fn ($item) => (float) $item->cost_price * (float) $item->quantity);

        return $this->createJournalEntry(
            branchId: $invoice->branch_id,
            description: "Sale invoice {$invoice->invoice_number}",
            lines: [
                ['account_code' => '1100', 'debit' => $total, 'credit' => 0, 'description' => 'Accounts receivable / cash'],
                ['account_code' => '4100', 'debit' => 0, 'credit' => $netSales, 'description' => 'Sales revenue'],
                ['account_code' => '2200', 'debit' => 0, 'credit' => $tax, 'description' => 'Tax payable'],
                ['account_code' => '5100', 'debit' => $cogs, 'credit' => 0, 'description' => 'Cost of goods sold'],
                ['account_code' => '1200', 'debit' => 0, 'credit' => $cogs, 'description' => 'Inventory'],
            ],
            reference: $invoice,
            user: $user,
        );
    }

    public function postPurchaseEntry(PurchaseInvoice $invoice, ?User $user = null): JournalEntry
    {
        $total = (float) $invoice->total;
        $tax = (float) $invoice->tax;
        $inventory = $total - $tax;

        return $this->createJournalEntry(
            branchId: $invoice->branch_id,
            description: "Purchase invoice {$invoice->invoice_number}",
            lines: [
                ['account_code' => '1200', 'debit' => $inventory, 'credit' => 0, 'description' => 'Inventory'],
                ['account_code' => '1400', 'debit' => $tax, 'credit' => 0, 'description' => 'Input tax'],
                ['account_code' => '2100', 'debit' => 0, 'credit' => $total, 'description' => 'Accounts payable'],
            ],
            reference: $invoice,
            user: $user,
        );
    }

    public function postExpenseEntry(Expense $expense, ?User $user = null): JournalEntry
    {
        $amount = (float) $expense->amount;

        $expenseAccountId = $expense->account_id
            ?? ChartOfAccount::query()->where('type', AccountType::Expense->value)->value('id');

        if ($expenseAccountId === null) {
            throw new RuntimeException('No expense account configured.');
        }

        return $this->createJournalEntry(
            branchId: $expense->branch_id,
            description: $expense->description ?? "Expense: {$expense->category}",
            lines: [
                ['account_id' => $expenseAccountId, 'debit' => $amount, 'credit' => 0, 'description' => $expense->category],
                ['account_code' => '1100', 'debit' => 0, 'credit' => $amount, 'description' => 'Cash / bank'],
            ],
            reference: $expense,
            user: $user,
        );
    }

    /**
     * @param  list<array{account_id?: int, account_code?: string, debit: float|int|string, credit: float|int|string, description?: string|null}>  $lines
     */
    public function createJournalEntry(
        int $branchId,
        string $description,
        array $lines,
        ?Model $reference = null,
        ?User $user = null,
        ?string $date = null,
    ): JournalEntry {
        return DB::transaction(function () use ($branchId, $description, $lines, $reference, $user, $date) {
            $resolvedLines = collect($lines)->map(function (array $line) {
                $accountId = $line['account_id'] ?? null;

                if ($accountId === null) {
                    $code = $line['account_code'] ?? null;

                    if ($code === null) {
                        throw new RuntimeException('Each journal line requires account_id or account_code.');
                    }

                    $accountId = ChartOfAccount::query()
                        ->where('code', $code)
                        ->value('id');

                    if ($accountId === null) {
                        throw new RuntimeException("Chart of account [{$code}] not found.");
                    }
                }

                return [
                    'account_id' => $accountId,
                    'debit' => (float) $line['debit'],
                    'credit' => (float) $line['credit'],
                    'description' => $line['description'] ?? null,
                ];
            });

            $totalDebit = $resolvedLines->sum('debit');
            $totalCredit = $resolvedLines->sum('credit');

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new RuntimeException('Journal entry debits and credits must balance.');
            }

            $entry = JournalEntry::create([
                'branch_id' => $branchId,
                'entry_number' => $this->numberGenerator->next('JE', JournalEntry::class, 'entry_number'),
                'date' => $date ?? now()->toDateString(),
                'description' => $description,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'status' => JournalEntryStatus::Posted->value,
                'user_id' => $user?->id ?? auth()->id(),
            ]);

            foreach ($resolvedLines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'description' => $line['description'],
                ]);
            }

            return $entry->fresh('lines');
        });
    }
}
