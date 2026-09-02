<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Admin\System\AdminController;

use App\Enums\JournalEntryStatus;
use App\Http\Requests\Admin\Accounting\JournalEntry\StoreJournalEntryRequest;
use App\Http\Requests\Admin\Accounting\JournalEntry\UpdateJournalEntryRequest;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PurchaseInvoice;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends AdminController
{
    public function __construct(
        private AccountingService $accountingService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $baseQuery = $this->forBranch(
            JournalEntry::query()->with(['user:id,name']),
            $request,
        )->withCount('lines');

        $entries = (clone $baseQuery)
            ->latest('date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totals = $this->entryTotals($entries->getCollection()->pluck('id')->all());
        $entries->through(fn (JournalEntry $entry) => $this->transformSummary($entry, $totals));

        $allEntries = (clone $baseQuery)->get();
        $allTotals = $this->entryTotals($allEntries->pluck('id')->all());

        return Inertia::render('admin/journal-entries/index', [
            'entries' => $entries,
            'summary' => $this->buildSummary($allEntries, $allTotals),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', JournalEntry::class);

        return Inertia::render('admin/journal-entries/create', [
            'accounts' => $this->accountOptions(),
            'defaultDate' => now()->toDateString(),
        ]);
    }

    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $this->authorize('create', JournalEntry::class);

        $data = $request->validated();
        $lines = Arr::pull($data, 'lines');

        $entry = $this->accountingService->createJournalEntry(
            branchId: $this->branchId($request) ?? $request->user()->branch_id,
            description: $data['description'],
            lines: $lines,
            user: $request->user(),
            date: $data['date'],
        );

        return to_route('admin.journal-entries.show', $entry)
            ->with('toast', ['type' => 'success', 'message' => __('toast.journal_created')]);
    }

    public function show(JournalEntry $journalEntry): Response
    {
        $this->authorize('view', $journalEntry);

        $journalEntry->load([
            'user:id,name',
            'lines.account:id,code,name,type',
        ]);

        return Inertia::render('admin/journal-entries/show', [
            'entry' => $this->transformDetail($journalEntry),
        ]);
    }

    public function edit(JournalEntry $journalEntry): Response|RedirectResponse
    {
        $this->authorize('update', $journalEntry);

        if (! $this->canModify($journalEntry)) {
            return to_route('admin.journal-entries.show', $journalEntry)
                ->with('toast', [
                    'type' => 'error',
                    'message' => __('toast.journal_system_locked'),
                ]);
        }

        $journalEntry->load(['lines.account:id,code,name']);

        return Inertia::render('admin/journal-entries/edit', [
            'entry' => $this->transformDetail($journalEntry),
            'accounts' => $this->accountOptions(),
        ]);
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $journalEntry): RedirectResponse
    {
        $this->authorize('update', $journalEntry);

        if (! $this->canModify($journalEntry)) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.journal_system_locked'),
            ]);
        }

        DB::transaction(function () use ($request, $journalEntry) {
            $data = $request->validated();
            $lines = Arr::pull($data, 'lines');

            $totalDebit = round(collect($lines)->sum(fn ($line) => (float) $line['debit']), 2);
            $totalCredit = round(collect($lines)->sum(fn ($line) => (float) $line['credit']), 2);

            if ($totalDebit !== $totalCredit) {
                throw ValidationException::withMessages([
                    'lines' => 'مجموع المدين يجب أن يساوي مجموع الدائن.',
                ]);
            }

            $journalEntry->update($data);
            $journalEntry->lines()->delete();

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'description' => $line['description'] ?? null,
                ]);
            }
        });

        return to_route('admin.journal-entries.show', $journalEntry)
            ->with('toast', ['type' => 'success', 'message' => __('toast.journal_updated')]);
    }

    public function destroy(JournalEntry $journalEntry): RedirectResponse
    {
        $this->authorize('delete', $journalEntry);

        if (! $this->canModify($journalEntry)) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.journal_system_delete'),
            ]);
        }

        $journalEntry->delete();

        return to_route('admin.journal-entries.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.journal_deleted')]);
    }

    /** @return list<array{id: int, code: string, name: string, type_label: string}> */
    private function accountOptions(): array
    {
        return ChartOfAccount::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type'])
            ->map(fn (ChartOfAccount $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type_label' => match ($account->type->value) {
                    'asset' => 'أصول',
                    'liability' => 'خصوم',
                    'equity' => 'حقوق ملكية',
                    'revenue' => 'إيرادات',
                    'expense' => 'مصروفات',
                    default => $account->type->value,
                },
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $entryIds
     * @return array<int, array{debit: float, credit: float}>
     */
    private function entryTotals(array $entryIds): array
    {
        if ($entryIds === []) {
            return [];
        }

        return JournalEntryLine::query()
            ->selectRaw('journal_entry_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->whereIn('journal_entry_id', $entryIds)
            ->groupBy('journal_entry_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->journal_entry_id => [
                    'debit' => round((float) $row->total_debit, 2),
                    'credit' => round((float) $row->total_credit, 2),
                ],
            ])
            ->all();
    }

    /** @param  array<int, array{debit: float, credit: float}>  $totals */
    private function buildSummary(Collection $entries, array $totals): array
    {
        return [
            'entries_count' => $entries->count(),
            'posted_count' => $entries->where('status', JournalEntryStatus::Posted)->count(),
            'manual_count' => $entries->whereNull('reference_type')->count(),
            'system_count' => $entries->whereNotNull('reference_type')->count(),
            'total_debit' => round(collect($totals)->sum('debit'), 2),
            'total_credit' => round(collect($totals)->sum('credit'), 2),
            'this_month_count' => $entries
                ->filter(fn (JournalEntry $entry) => $entry->date?->isCurrentMonth())
                ->count(),
        ];
    }

    /** @param  array<int, array{debit: float, credit: float}>  $totals */
    private function transformSummary(JournalEntry $entry, array $totals): array
    {
        $lineTotals = $totals[$entry->id] ?? ['debit' => 0.0, 'credit' => 0.0];

        return [
            'id' => $entry->id,
            'entry_number' => $entry->entry_number,
            'date' => $entry->date?->toDateString(),
            'description' => $entry->description,
            'status' => $entry->status->value,
            'status_label' => $this->statusLabel($entry->status),
            'user_name' => $entry->user?->name,
            'lines_count' => $entry->lines_count ?? 0,
            'total_debit' => $lineTotals['debit'],
            'total_credit' => $lineTotals['credit'],
            'reference_label' => $this->referenceLabel($entry),
            'reference_url' => $this->referenceUrl($entry),
            'is_manual' => $entry->reference_type === null,
            'can_edit' => $this->canModify($entry),
            'can_delete' => $this->canModify($entry),
        ];
    }

    private function transformDetail(JournalEntry $entry): array
    {
        $data = $this->transformSummary($entry, $this->entryTotals([$entry->id]));

        $data['lines'] = $entry->relationLoaded('lines')
            ? $entry->lines->map(fn (JournalEntryLine $line) => [
                'id' => $line->id,
                'account_id' => $line->account_id,
                'account_code' => $line->account?->code,
                'account_name' => $line->account?->name,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'description' => $line->description,
            ])->values()->all()
            : [];

        return $data;
    }

    private function canModify(JournalEntry $entry): bool
    {
        return $entry->reference_type === null
            && $entry->status !== JournalEntryStatus::Reversed;
    }

    private function statusLabel(JournalEntryStatus $status): string
    {
        return match ($status) {
            JournalEntryStatus::Draft => 'مسودة',
            JournalEntryStatus::Posted => 'مرحّل',
            JournalEntryStatus::Reversed => 'معكوس',
        };
    }

    private function referenceLabel(JournalEntry $entry): ?string
    {
        if ($entry->reference_type === null) {
            return null;
        }

        return match ($entry->reference_type) {
            Expense::class => 'مصروف',
            Invoice::class => 'فاتورة مبيعات',
            PurchaseInvoice::class => 'فاتورة شراء',
            default => 'عملية نظام',
        };
    }

    private function referenceUrl(JournalEntry $entry): ?string
    {
        if ($entry->reference_type === null || $entry->reference_id === null) {
            return null;
        }

        return match ($entry->reference_type) {
            Expense::class => route('admin.expenses.show', $entry->reference_id),
            Invoice::class => route('admin.invoices.show', $entry->reference_id),
            PurchaseInvoice::class => route('admin.purchase-invoices.show', $entry->reference_id),
            default => null,
        };
    }
}
