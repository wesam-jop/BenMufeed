<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Admin\System\AdminController;

use App\Http\Requests\Admin\Accounting\CashRegisterSession\CloseCashRegisterSessionRequest;
use App\Http\Requests\Admin\Accounting\CashRegisterSession\StoreCashRegisterSessionRequest;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Services\Accounting\CashRegisterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CashRegisterSessionController extends AdminController
{
    public function __construct(private CashRegisterService $cashRegisterService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CashRegisterSession::class);

        $baseQuery = $this->forBranchThrough(
            CashRegisterSession::query()->with([
                'cashRegister:id,name',
                'user:id,name',
            ]),
            $request,
            'cashRegister',
        );

        $sessions = (clone $baseQuery)
            ->latest('opened_at')
            ->paginate(15)
            ->withQueryString();

        $sessions->through(fn (CashRegisterSession $session) => $this->transformSummary($session));

        $allSessions = (clone $baseQuery)->get();

        return Inertia::render('admin/cash-register-sessions/index', [
            'cashRegisterSessions' => $sessions,
            'summary' => [
                'sessions_count' => $allSessions->count(),
                'open_count' => $allSessions->where('status', 'open')->count(),
                'closed_count' => $allSessions->where('status', 'closed')->count(),
                'total_expected' => round((float) $allSessions->sum('expected_cash'), 2),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', CashRegisterSession::class);

        return Inertia::render('admin/cash-register-sessions/create', [
            'cashRegisters' => $this->forBranch(CashRegister::query(), $request)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreCashRegisterSessionRequest $request): RedirectResponse
    {
        $this->authorize('create', CashRegisterSession::class);

        $register = CashRegister::query()->findOrFail($request->validated('cash_register_id'));

        $existing = CashRegisterSession::query()
            ->where('cash_register_id', $register->id)
            ->whereDate('session_date', now()->toDateString())
            ->where('status', 'open')
            ->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'cash_register_id' => 'يوجد جلسة مفتوحة لهذا الصندوق اليوم.',
            ]);
        }

        $session = $this->cashRegisterService->openSession(
            $register,
            (float) $request->validated('opening_balance'),
            $request->user(),
            $request->validated('notes'),
        );

        return to_route('admin.cash-register-sessions.show', $session)
            ->with('toast', ['type' => 'success', 'message' => __('toast.session_opened')]);
    }

    public function show(CashRegisterSession $cashRegisterSession): Response
    {
        $this->authorize('view', $cashRegisterSession);

        $cashRegisterSession->load([
            'cashRegister:id,name',
            'user:id,name',
            'cashMovements',
            'payments',
        ]);

        $summary = $this->cashRegisterService->dailySummary($cashRegisterSession);

        return Inertia::render('admin/cash-register-sessions/show', [
            'cashRegisterSession' => [
                ...$this->transformSummary($cashRegisterSession),
                'notes' => $cashRegisterSession->notes,
                'summary' => $summary,
                'movements' => $cashRegisterSession->cashMovements
                    ->sortByDesc('id')
                    ->values()
                    ->map(fn ($movement) => [
                        'id' => $movement->id,
                        'type' => $movement->type,
                        'type_label' => $this->movementTypeLabel($movement->type),
                        'amount' => (float) $movement->amount,
                        'notes' => $movement->notes,
                        'created_at' => $movement->created_at?->toIso8601String(),
                    ])
                    ->all(),
            ],
        ]);
    }

    public function edit(CashRegisterSession $cashRegisterSession): RedirectResponse
    {
        $this->authorize('view', $cashRegisterSession);

        return to_route('admin.cash-register-sessions.show', $cashRegisterSession);
    }

    public function update(Request $request, CashRegisterSession $cashRegisterSession): RedirectResponse
    {
        abort(405, 'لا يمكن تعديل الجلسة مباشرة — استخدم الإغلاق.');
    }

    public function close(
        CloseCashRegisterSessionRequest $request,
        CashRegisterSession $cashRegisterSession,
    ): RedirectResponse {
        $this->authorize('update', $cashRegisterSession);

        $this->cashRegisterService->closeSession(
            $cashRegisterSession,
            (float) $request->validated('actual_cash'),
            $request->validated('notes'),
        );

        return to_route('admin.cash-register-sessions.show', $cashRegisterSession)
            ->with('toast', ['type' => 'success', 'message' => __('toast.session_closed')]);
    }

    public function destroy(CashRegisterSession $cashRegisterSession): RedirectResponse
    {
        $this->authorize('delete', $cashRegisterSession);

        if ($cashRegisterSession->payments()->exists() || $cashRegisterSession->cashMovements()->exists()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.session_has_movements'),
            ]);
        }

        $cashRegisterSession->delete();

        return to_route('admin.cash-register-sessions.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.session_deleted')]);
    }

    /** @return array<string, mixed> */
    private function transformSummary(CashRegisterSession $session): array
    {
        return [
            'id' => $session->id,
            'cash_register_name' => $session->cashRegister?->name ?? '—',
            'user_name' => $session->user?->name,
            'status' => $session->status,
            'status_label' => $session->status === 'open' ? 'مفتوحة' : 'مغلقة',
            'session_date' => $session->session_date?->toDateString()
                ?? $session->opened_at?->toDateString(),
            'opening_balance' => (float) $session->opening_balance,
            'expected_cash' => (float) $session->expected_cash,
            'actual_cash' => $session->actual_cash !== null ? (float) $session->actual_cash : null,
            'difference' => $session->difference !== null ? (float) $session->difference : null,
            'opened_at' => $session->opened_at?->toIso8601String(),
            'closed_at' => $session->closed_at?->toIso8601String(),
            'can_close' => $session->status === 'open',
        ];
    }

    private function movementTypeLabel(string $type): string
    {
        return match ($type) {
            'sale' => 'مبيعات',
            'deposit' => 'إيداع',
            'withdrawal' => 'سحب',
            'expense' => 'مصروف',
            'refund' => 'مرتجع',
            'adjustment' => 'تعديل',
            default => $type,
        };
    }
}
