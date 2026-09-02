<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Admin\System\AdminController;

use App\Enums\AccountType;
use App\Http\Requests\Admin\Accounting\ChartOfAccount\StoreChartOfAccountRequest;
use App\Http\Requests\Admin\Accounting\ChartOfAccount\UpdateChartOfAccountRequest;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ChartOfAccountController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ChartOfAccount::class);

        $baseQuery = ChartOfAccount::query()
            ->with(['parent:id,code,name'])
            ->withCount(['children', 'journalEntryLines', 'expenses']);

        $accounts = (clone $baseQuery)
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        $balances = $this->accountBalances();
        $accounts->through(fn (ChartOfAccount $account) => $this->transformSummary($account, $balances));

        $allAccounts = (clone $baseQuery)->get();

        return Inertia::render('admin/chart-of-accounts/index', [
            'accounts' => $accounts,
            'summary' => $this->buildSummary($allAccounts, $balances),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ChartOfAccount::class);

        return Inertia::render('admin/chart-of-accounts/create', [
            'typeOptions' => $this->accountTypeOptions(),
            'parentOptions' => $this->parentAccountOptions(),
        ]);
    }

    public function store(StoreChartOfAccountRequest $request): RedirectResponse
    {
        $this->authorize('create', ChartOfAccount::class);

        $account = ChartOfAccount::create([
            ...$request->validated(),
            'is_system' => false,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return to_route('admin.chart-of-accounts.show', $account)
            ->with('toast', ['type' => 'success', 'message' => __('toast.account_created')]);
    }

    public function show(ChartOfAccount $chartOfAccount): Response
    {
        $this->authorize('view', $chartOfAccount);

        $chartOfAccount->load([
            'parent:id,code,name,type',
            'children' => fn ($query) => $query->orderBy('code')->withCount('journalEntryLines'),
        ])->loadCount(['journalEntryLines', 'expenses', 'children']);

        $balances = $this->accountBalances([$chartOfAccount->id]);
        $balance = $balances[$chartOfAccount->id] ?? ['debit' => 0.0, 'credit' => 0.0, 'balance' => 0.0];

        $recentLines = JournalEntryLine::query()
            ->with(['journalEntry:id,entry_number,date,description'])
            ->where('account_id', $chartOfAccount->id)
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (JournalEntryLine $line) => [
                'id' => $line->id,
                'entry_number' => $line->journalEntry?->entry_number,
                'date' => $line->journalEntry?->date?->toDateString(),
                'description' => $line->description ?? $line->journalEntry?->description,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
            ]);

        return Inertia::render('admin/chart-of-accounts/show', [
            'account' => $this->transformDetail($chartOfAccount, $balance),
            'recentLines' => $recentLines,
        ]);
    }

    public function edit(ChartOfAccount $chartOfAccount): Response
    {
        $this->authorize('update', $chartOfAccount);

        return Inertia::render('admin/chart-of-accounts/edit', [
            'account' => $this->transformDetail($chartOfAccount),
            'typeOptions' => $this->accountTypeOptions(),
            'parentOptions' => $this->parentAccountOptions($chartOfAccount),
        ]);
    }

    public function update(UpdateChartOfAccountRequest $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $this->authorize('update', $chartOfAccount);

        if ($chartOfAccount->is_system) {
            $chartOfAccount->update([
                'name' => $request->validated('name'),
                'is_active' => $request->boolean('is_active', true),
            ]);
        } else {
            $chartOfAccount->update([
                ...$request->validated(),
                'is_active' => $request->boolean('is_active', true),
            ]);
        }

        return to_route('admin.chart-of-accounts.show', $chartOfAccount)
            ->with('toast', ['type' => 'success', 'message' => __('toast.account_updated')]);
    }

    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $this->authorize('delete', $chartOfAccount);

        if ($chartOfAccount->is_system) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.account_system_delete'),
            ]);
        }

        if ($chartOfAccount->children()->exists()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.account_has_children'),
            ]);
        }

        if ($chartOfAccount->journalEntryLines()->exists() || $chartOfAccount->expenses()->exists()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.account_has_entries'),
            ]);
        }

        $chartOfAccount->delete();

        return to_route('admin.chart-of-accounts.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.account_deleted')]);
    }

    /** @return list<array{value: string, label: string}> */
    private function accountTypeOptions(): array
    {
        return collect(AccountType::cases())
            ->map(fn (AccountType $type) => [
                'value' => $type->value,
                'label' => $this->accountTypeLabel($type),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: int, code: string, name: string, type: string, type_label: string}> */
    private function parentAccountOptions(?ChartOfAccount $exclude = null): array
    {
        return ChartOfAccount::query()
            ->when($exclude, fn ($query) => $query->where('id', '!=', $exclude->id))
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type'])
            ->map(fn (ChartOfAccount $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type->value,
                'type_label' => $this->accountTypeLabel($account->type),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<int>|null  $accountIds
     * @return array<int, array{debit: float, credit: float, balance: float}>
     */
    private function accountBalances(?array $accountIds = null): array
    {
        return JournalEntryLine::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->when($accountIds !== null, fn ($query) => $query->whereIn('account_id', $accountIds))
            ->groupBy('account_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->account_id => [
                    'debit' => round((float) $row->total_debit, 2),
                    'credit' => round((float) $row->total_credit, 2),
                    'balance' => round((float) $row->total_debit - (float) $row->total_credit, 2),
                ],
            ])
            ->all();
    }

    /** @param  array<int, array{debit: float, credit: float, balance: float}>  $balances */
    private function buildSummary(Collection $accounts, array $balances): array
    {
        return [
            'accounts_count' => $accounts->count(),
            'active_count' => $accounts->where('is_active', true)->count(),
            'system_count' => $accounts->where('is_system', true)->count(),
            'by_type' => collect(AccountType::cases())
                ->mapWithKeys(fn (AccountType $type) => [
                    $type->value => $accounts->where('type', $type)->count(),
                ])
                ->all(),
            'total_debit' => round(collect($balances)->sum('debit'), 2),
            'total_credit' => round(collect($balances)->sum('credit'), 2),
        ];
    }

    /** @param  array{debit?: float, credit?: float, balance?: float}|null  $balance */
    private function transformSummary(ChartOfAccount $account, ?array $balances = null): array
    {
        $balance = $balances[$account->id] ?? ['debit' => 0.0, 'credit' => 0.0, 'balance' => 0.0];

        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type->value,
            'type_label' => $this->accountTypeLabel($account->type),
            'parent_code' => $account->parent?->code,
            'parent_name' => $account->parent?->name,
            'is_system' => $account->is_system,
            'is_active' => $account->is_active,
            'children_count' => $account->children_count ?? 0,
            'journal_lines_count' => $account->journal_entry_lines_count ?? 0,
            'expenses_count' => $account->expenses_count ?? 0,
            'debit' => $balance['debit'],
            'credit' => $balance['credit'],
            'balance' => $balance['balance'],
        ];
    }

    /** @param  array{debit?: float, credit?: float, balance?: float}|null  $balance */
    private function transformDetail(ChartOfAccount $account, ?array $balance = null): array
    {
        $data = $this->transformSummary($account, $balance !== null ? [$account->id => $balance] : null);

        $data['parent_id'] = $account->parent_id;
        $data['children'] = $account->relationLoaded('children')
            ? $account->children->map(fn (ChartOfAccount $child) => [
                'id' => $child->id,
                'code' => $child->code,
                'name' => $child->name,
                'type_label' => $this->accountTypeLabel($child->type),
                'is_active' => $child->is_active,
                'journal_lines_count' => $child->journal_entry_lines_count ?? 0,
            ])->values()->all()
            : [];

        return $data;
    }

    private function accountTypeLabel(AccountType $type): string
    {
        return match ($type) {
            AccountType::Asset => 'أصول',
            AccountType::Liability => 'خصوم',
            AccountType::Equity => 'حقوق ملكية',
            AccountType::Revenue => 'إيرادات',
            AccountType::Expense => 'مصروفات',
        };
    }
}
