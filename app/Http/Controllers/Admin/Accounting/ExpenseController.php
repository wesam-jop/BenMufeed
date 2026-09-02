<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Admin\System\AdminController;

use App\Enums\AccountType;
use App\Enums\CashMovementType;
use App\Enums\PaymentMethod;
use App\Http\Requests\Admin\Accounting\Expense\StoreExpenseRequest;
use App\Http\Requests\Admin\Accounting\Expense\UpdateExpenseRequest;
use App\Models\CashRegisterSession;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\CashRegisterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends AdminController
{
    public function __construct(
        private AccountingService $accountingService,
        private CashRegisterService $cashRegisterService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Expense::class);

        $baseQuery = $this->forBranch(
            Expense::query()->with(['user:id,name', 'account:id,name,code']),
            $request,
        );

        $expenses = (clone $baseQuery)
            ->latest('expense_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $expenses->through(fn (Expense $expense) => $this->transformSummary($expense));

        $allExpenses = (clone $baseQuery)->get();

        return Inertia::render('admin/expenses/index', [
            'expenses' => $expenses,
            'summary' => [
                'expenses_count' => $allExpenses->count(),
                'total_amount' => round((float) $allExpenses->sum('amount'), 2),
                'this_month_amount' => round(
                    (float) $allExpenses
                        ->filter(fn (Expense $expense) => $expense->expense_date?->isCurrentMonth())
                        ->sum('amount'),
                    2,
                ),
                'this_month_count' => $allExpenses
                    ->filter(fn (Expense $expense) => $expense->expense_date?->isCurrentMonth())
                    ->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Expense::class);

        return Inertia::render('admin/expenses/create', [
            'accounts' => $this->expenseAccounts(),
            'paymentMethods' => $this->paymentMethodOptions(),
            'categories' => $this->categoryOptions(),
            'defaultDate' => now()->toDateString(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $expense = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $paymentMethod = PaymentMethod::from($data['payment_method']);

            $sessionId = null;

            if ($paymentMethod === PaymentMethod::Cash) {
                $session = CashRegisterSession::query()
                    ->where('status', 'open')
                    ->whereHas('cashRegister', function ($query) use ($request) {
                        $branchId = $this->branchId($request) ?? $request->user()->branch_id;
                        $query->where('branch_id', $branchId)->where('is_active', true);
                    })
                    ->latest('opened_at')
                    ->first();

                $sessionId = $session?->id;
            }

            $expense = Expense::create([
                ...$data,
                'branch_id' => $this->branchId($request) ?? $request->user()->branch_id,
                'user_id' => $request->user()->id,
                'cash_register_session_id' => $sessionId,
            ]);

            if ($sessionId !== null) {
                $this->cashRegisterService->recordMovement(
                    $expense->cashRegisterSession,
                    CashMovementType::Expense,
                    (float) $expense->amount,
                    $expense,
                    $expense->description ?? $expense->category,
                );
            }

            $this->accountingService->postExpenseEntry($expense, $request->user());

            return $expense;
        });

        return to_route('admin.expenses.show', $expense)
            ->with('toast', ['type' => 'success', 'message' => __('toast.expense_created')]);
    }

    public function show(Expense $expense): Response
    {
        $this->authorize('view', $expense);

        $expense->load(['user:id,name', 'account:id,name,code', 'cashRegisterSession.cashRegister:id,name']);

        return Inertia::render('admin/expenses/show', [
            'expense' => $this->transformDetail($expense),
        ]);
    }

    public function edit(Expense $expense): Response
    {
        $this->authorize('update', $expense);

        return Inertia::render('admin/expenses/edit', [
            'expense' => $this->transformDetail($expense),
            'accounts' => $this->expenseAccounts(),
            'paymentMethods' => $this->paymentMethodOptions(),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $expense->update($request->validated());

        return to_route('admin.expenses.show', $expense)
            ->with('toast', ['type' => 'success', 'message' => __('toast.expense_updated')]);
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $expense->delete();

        return to_route('admin.expenses.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.expense_deleted')]);
    }

    /** @return list<array{id: int, name: string, code: string}> */
    private function expenseAccounts(): array
    {
        return ChartOfAccount::query()
            ->where('type', AccountType::Expense)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'name', 'code'])
            ->map(fn (ChartOfAccount $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->code,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{value: string, label: string}> */
    private function paymentMethodOptions(): array
    {
        return [
            ['value' => PaymentMethod::Cash->value, 'label' => 'نقدي'],
            ['value' => PaymentMethod::Card->value, 'label' => 'بطاقة'],
            ['value' => PaymentMethod::BankTransfer->value, 'label' => 'تحويل بنكي'],
            ['value' => PaymentMethod::Other->value, 'label' => 'أخرى'],
        ];
    }

    /** @return list<string> */
    private function categoryOptions(): array
    {
        return [
            'تشغيل',
            'إيجار',
            'مرافق',
            'رواتب',
            'صيانة',
            'مشتريات تشغيلية',
            'تسويق',
            'نقل',
            'أخرى',
        ];
    }

    /** @return array<string, mixed> */
    private function transformSummary(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'category' => $expense->category,
            'description' => $expense->description,
            'amount' => (float) $expense->amount,
            'payment_method' => $expense->payment_method->value,
            'payment_method_label' => $this->paymentMethodLabel($expense->payment_method),
            'expense_date' => $expense->expense_date?->toDateString(),
            'user_name' => $expense->user?->name,
            'account_name' => $expense->account?->name,
            'created_at' => $expense->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function transformDetail(Expense $expense): array
    {
        return [
            ...$this->transformSummary($expense),
            'account_id' => $expense->account_id,
            'account_code' => $expense->account?->code,
            'cash_register_session_id' => $expense->cash_register_session_id,
            'cash_register_name' => $expense->cashRegisterSession?->cashRegister?->name,
        ];
    }

    private function paymentMethodLabel(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::Cash => 'نقدي',
            PaymentMethod::Card => 'بطاقة',
            PaymentMethod::BankTransfer => 'تحويل بنكي',
            PaymentMethod::Wallet => 'محفظة',
            PaymentMethod::Loyalty => 'نقاط',
            PaymentMethod::Other => 'أخرى',
        };
    }
}
