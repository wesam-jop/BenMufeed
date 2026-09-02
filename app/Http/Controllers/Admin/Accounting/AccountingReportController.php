<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Admin\System\AdminController;

use App\Admin\AccountingReport;
use App\Enums\AccountType;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AccountingReportController extends AdminController
{
    public function profitLoss(Request $request): Response
    {
        $this->authorize('profitLoss', AccountingReport::class);

        $branchId = $this->branchId($request);
        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        $aggregates = $this->aggregatedLines($branchId, $from, $to);

        $revenue = [];
        $cogs = [];
        $expenses = [];

        foreach ($aggregates as $row) {
            $account = $row->account;

            if ($account === null) {
                continue;
            }

            $item = $this->transformAccountAmount($account, $row);

            if ($item === null || $item['amount'] === 0.0) {
                continue;
            }

            if ($account->type === AccountType::Revenue) {
                $revenue[] = $item;

                continue;
            }

            if ($account->type !== AccountType::Expense) {
                continue;
            }

            if (str_starts_with($account->code, '5')) {
                $cogs[] = $item;
            } else {
                $expenses[] = $item;
            }
        }

        usort($revenue, fn ($a, $b) => strcmp($a['code'], $b['code']));
        usort($cogs, fn ($a, $b) => strcmp($a['code'], $b['code']));
        usort($expenses, fn ($a, $b) => strcmp($a['code'], $b['code']));

        $totalRevenue = round(collect($revenue)->sum('amount'), 2);
        $totalCogs = round(collect($cogs)->sum('amount'), 2);
        $grossProfit = round($totalRevenue - $totalCogs, 2);
        $totalExpenses = round(collect($expenses)->sum('amount'), 2);
        $netProfit = round($grossProfit - $totalExpenses, 2);

        return Inertia::render('admin/accounting/reports/profit-loss', [
            'report' => [
                'revenue' => $revenue,
                'cogs' => $cogs,
                'expenses' => $expenses,
                'summary' => [
                    'total_revenue' => $totalRevenue,
                    'total_cogs' => $totalCogs,
                    'gross_profit' => $grossProfit,
                    'total_expenses' => $totalExpenses,
                    'net_profit' => $netProfit,
                ],
            ],
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        $this->authorize('balanceSheet', AccountingReport::class);

        $branchId = $this->branchId($request);
        $asOf = $request->date('as_of')?->toDateString() ?? now()->toDateString();

        $aggregates = $this->aggregatedLines($branchId, null, $asOf);

        $assets = [];
        $liabilities = [];
        $equity = [];
        $netIncome = 0.0;

        foreach ($aggregates as $row) {
            $account = $row->account;

            if ($account === null) {
                continue;
            }

            if ($account->type === AccountType::Revenue) {
                $netIncome += round((float) $row->total_credit - (float) $row->total_debit, 2);

                continue;
            }

            if ($account->type === AccountType::Expense) {
                $netIncome -= round((float) $row->total_debit - (float) $row->total_credit, 2);

                continue;
            }

            if (! in_array($account->type, [AccountType::Asset, AccountType::Liability, AccountType::Equity], true)) {
                continue;
            }

            $item = $this->transformBalanceSheetLine($account, $row);

            if ($item === null || $item['amount'] === 0.0) {
                continue;
            }

            match ($account->type) {
                AccountType::Asset => $assets[] = $item,
                AccountType::Liability => $liabilities[] = $item,
                AccountType::Equity => $equity[] = $item,
                default => null,
            };
        }

        $netIncome = round($netIncome, 2);

        if ($netIncome !== 0.0) {
            $equity[] = [
                'account_id' => null,
                'code' => '—',
                'name' => $netIncome >= 0 ? 'أرباح الفترة المتراكمة' : 'خسائر الفترة المتراكمة',
                'category' => 'retained_earnings',
                'category_label' => 'نتيجة الفترة',
                'debit' => $netIncome < 0 ? round(abs($netIncome), 2) : 0.0,
                'credit' => $netIncome > 0 ? $netIncome : 0.0,
                'amount' => $netIncome,
                'is_computed' => true,
            ];
        }

        foreach ([$assets, $liabilities, $equity] as &$group) {
            usort($group, fn ($a, $b) => strcmp($a['code'], $b['code']));
        }

        $totalAssets = round(collect($assets)->sum('amount'), 2);
        $totalLiabilities = round(collect($liabilities)->sum('amount'), 2);
        $totalEquity = round(collect($equity)->sum('amount'), 2);
        $totalLiabilitiesAndEquity = round($totalLiabilities + $totalEquity, 2);
        $difference = round($totalAssets - $totalLiabilitiesAndEquity, 2);

        return Inertia::render('admin/accounting/reports/balance-sheet', [
            'report' => [
                'sections' => [
                    [
                        'key' => 'assets',
                        'title' => 'الأصول',
                        'groups' => $this->groupBalanceSheetLines($assets, AccountType::Asset),
                        'total' => $totalAssets,
                    ],
                    [
                        'key' => 'liabilities',
                        'title' => 'الخصوم',
                        'groups' => $this->groupBalanceSheetLines($liabilities, AccountType::Liability),
                        'total' => $totalLiabilities,
                    ],
                    [
                        'key' => 'equity',
                        'title' => 'حقوق الملكية',
                        'groups' => $this->groupBalanceSheetLines($equity, AccountType::Equity),
                        'total' => $totalEquity,
                    ],
                ],
                'summary' => [
                    'total_assets' => $totalAssets,
                    'total_liabilities' => $totalLiabilities,
                    'total_equity' => $totalEquity,
                    'net_income' => $netIncome,
                    'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
                    'difference' => $difference,
                    'is_balanced' => abs($difference) < 0.01,
                    'assets_count' => count($assets),
                    'liabilities_count' => count($liabilities),
                    'equity_count' => count($equity),
                ],
            ],
            'filters' => [
                'as_of' => $asOf,
            ],
        ]);
    }

    public function trialBalance(Request $request): Response
    {
        $this->authorize('trialBalance', AccountingReport::class);

        $branchId = $this->branchId($request);
        $asOf = $request->date('as_of')?->toDateString() ?? now()->toDateString();

        $aggregates = $this->aggregatedLines($branchId, null, $asOf);

        $lines = $aggregates
            ->map(function ($row) {
                $account = $row->account;

                if ($account === null) {
                    return null;
                }

                $debit = round((float) $row->total_debit, 2);
                $credit = round((float) $row->total_credit, 2);

                if ($debit === 0.0 && $credit === 0.0) {
                    return null;
                }

                $balance = round($debit - $credit, 2);
                $debitBalance = $balance > 0 ? $balance : 0.0;
                $creditBalance = $balance < 0 ? round(abs($balance), 2) : 0.0;

                return [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type->value,
                    'type_label' => $this->accountTypeLabel($account->type),
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $balance,
                    'debit_balance' => $debitBalance,
                    'credit_balance' => $creditBalance,
                    'normal_side' => $this->normalBalanceSide($account->type),
                ];
            })
            ->filter()
            ->sortBy('code')
            ->values()
            ->all();

        $totalDebit = round(collect($lines)->sum('debit'), 2);
        $totalCredit = round(collect($lines)->sum('credit'), 2);
        $totalDebitBalance = round(collect($lines)->sum('debit_balance'), 2);
        $totalCreditBalance = round(collect($lines)->sum('credit_balance'), 2);
        $difference = round($totalDebit - $totalCredit, 2);

        $sections = collect(AccountType::cases())
            ->map(function (AccountType $type) use ($lines) {
                $typeLines = collect($lines)
                    ->where('type', $type->value)
                    ->values()
                    ->all();

                if ($typeLines === []) {
                    return null;
                }

                return [
                    'key' => $type->value,
                    'title' => $this->accountTypeLabel($type),
                    'lines' => $typeLines,
                    'summary' => [
                        'accounts_count' => count($typeLines),
                        'total_debit' => round(collect($typeLines)->sum('debit'), 2),
                        'total_credit' => round(collect($typeLines)->sum('credit'), 2),
                        'debit_balance' => round(collect($typeLines)->sum('debit_balance'), 2),
                        'credit_balance' => round(collect($typeLines)->sum('credit_balance'), 2),
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();

        return Inertia::render('admin/accounting/reports/trial-balance', [
            'report' => [
                'sections' => $sections,
                'lines' => $lines,
                'summary' => [
                    'accounts_count' => count($lines),
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'total_debit_balance' => $totalDebitBalance,
                    'total_credit_balance' => $totalCreditBalance,
                    'difference' => $difference,
                    'is_balanced' => abs($difference) < 0.01,
                    'by_type' => collect($lines)
                        ->groupBy('type')
                        ->map(fn ($group) => $group->count())
                        ->all(),
                ],
            ],
            'filters' => [
                'as_of' => $asOf,
            ],
        ]);
    }

    private function aggregatedLines(?int $branchId, ?string $from, ?string $to): Collection
    {
        return JournalEntryLine::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->with('account:id,code,name,type')
            ->when($branchId, fn ($q) => $q->whereHas(
                'journalEntry',
                fn ($jq) => $jq->where('branch_id', $branchId),
            ))
            ->when($from, fn ($q) => $q->whereHas(
                'journalEntry',
                fn ($jq) => $jq->whereDate('date', '>=', $from),
            ))
            ->when($to, fn ($q) => $q->whereHas(
                'journalEntry',
                fn ($jq) => $jq->whereDate('date', '<=', $to),
            ))
            ->groupBy('account_id')
            ->get();
    }

    /** @return array{account_id: int, code: string, name: string, amount: float}|null */
    private function transformAccountAmount(ChartOfAccount $account, object $row): ?array
    {
        $line = $this->transformBalanceSheetLine($account, $row);

        if ($line === null) {
            return null;
        }

        return [
            'account_id' => $line['account_id'],
            'code' => $line['code'],
            'name' => $line['name'],
            'amount' => $line['amount'],
        ];
    }

    /** @return array{account_id: int|null, code: string, name: string, category: string, category_label: string, debit: float, credit: float, amount: float, is_computed?: bool}|null */
    private function transformBalanceSheetLine(ChartOfAccount $account, object $row): ?array
    {
        $debit = round((float) $row->total_debit, 2);
        $credit = round((float) $row->total_credit, 2);

        $amount = match ($account->type) {
            AccountType::Asset, AccountType::Expense => round($debit - $credit, 2),
            AccountType::Liability, AccountType::Equity, AccountType::Revenue => round($credit - $debit, 2),
        };

        $category = $this->balanceSheetCategory($account->code, $account->type);

        return [
            'account_id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'category' => $category['key'],
            'category_label' => $category['label'],
            'debit' => $debit,
            'credit' => $credit,
            'amount' => $amount,
        ];
    }

    /** @param  list<array{category: string, category_label: string, amount: float}>  $lines
     * @return list<array{key: string, label: string, lines: list<array<string, mixed>>, total: float}>
     */
    private function groupBalanceSheetLines(array $lines, AccountType $type): array
    {
        $groups = [];

        foreach ($lines as $line) {
            $key = $line['category'];

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'label' => $line['category_label'],
                    'lines' => [],
                    'total' => 0.0,
                ];
            }

            $groups[$key]['lines'][] = $line;
            $groups[$key]['total'] = round($groups[$key]['total'] + $line['amount'], 2);
        }

        return array_values($groups);
    }

    /** @return array{key: string, label: string} */
    private function balanceSheetCategory(string $code, AccountType $type): array
    {
        return match ($type) {
            AccountType::Asset => match (true) {
                str_starts_with($code, '11') => ['key' => 'cash_bank', 'label' => 'نقدية وبنوك'],
                str_starts_with($code, '12') => ['key' => 'inventory', 'label' => 'المخزون'],
                str_starts_with($code, '14') => ['key' => 'input_tax', 'label' => 'ضريبة المدخلات'],
                default => ['key' => 'other_assets', 'label' => 'أصول أخرى'],
            },
            AccountType::Liability => match (true) {
                str_starts_with($code, '21') => ['key' => 'payables', 'label' => 'ذمم الموردين'],
                str_starts_with($code, '22') => ['key' => 'output_tax', 'label' => 'ضريبة المخرجات'],
                default => ['key' => 'other_liabilities', 'label' => 'خصوم أخرى'],
            },
            AccountType::Equity => ['key' => 'equity', 'label' => 'رأس المال وحقوق الملكية'],
        };
    }

    private function normalBalanceSide(AccountType $type): string
    {
        return match ($type) {
            AccountType::Asset, AccountType::Expense => 'debit',
            AccountType::Liability, AccountType::Equity, AccountType::Revenue => 'credit',
        };
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
