<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Admin\System\AdminController;

use App\Http\Requests\Admin\Accounting\CashRegister\StoreCashRegisterRequest;
use App\Http\Requests\Admin\Accounting\CashRegister\UpdateCashRegisterRequest;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashRegisterController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CashRegister::class);

        $baseQuery = $this->forBranch(CashRegister::query(), $request);

        $cashRegisters = (clone $baseQuery)
            ->withCount([
                'sessions',
                'sessions as open_sessions_count' => fn ($query) => $query->where('status', 'open'),
            ])
            ->with([
                'sessions' => fn ($query) => $query
                    ->with('user:id,name')
                    ->latest('opened_at')
                    ->limit(1),
            ])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $cashRegisters->through(fn (CashRegister $register) => $this->transformSummary($register));

        $allRegisters = (clone $baseQuery)
            ->withCount([
                'sessions as open_sessions_count' => fn ($query) => $query->where('status', 'open'),
            ])
            ->get();

        return Inertia::render('admin/cash-registers/index', [
            'cashRegisters' => $cashRegisters,
            'summary' => [
                'registers_count' => $allRegisters->count(),
                'active_count' => $allRegisters->where('is_active', true)->count(),
                'open_sessions_count' => (int) $allRegisters->sum('open_sessions_count'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CashRegister::class);

        return Inertia::render('admin/cash-registers/create');
    }

    public function store(StoreCashRegisterRequest $request): RedirectResponse
    {
        $this->authorize('create', CashRegister::class);

        $cashRegister = CashRegister::create([
            ...$request->validated(),
            'branch_id' => $this->branchId($request) ?? $request->user()->branch_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return to_route('admin.cash-registers.show', $cashRegister)
            ->with('toast', ['type' => 'success', 'message' => __('toast.cash_register_created')]);
    }

    public function show(CashRegister $cashRegister): Response
    {
        $this->authorize('view', $cashRegister);

        $cashRegister->loadCount([
            'sessions',
            'sessions as open_sessions_count' => fn ($query) => $query->where('status', 'open'),
        ]);

        $sessions = CashRegisterSession::query()
            ->where('cash_register_id', $cashRegister->id)
            ->with('user:id,name')
            ->latest('opened_at')
            ->limit(20)
            ->get()
            ->map(fn (CashRegisterSession $session) => $this->transformSession($session))
            ->values()
            ->all();

        return Inertia::render('admin/cash-registers/show', [
            'cashRegister' => [
                ...$this->transformSummary($cashRegister),
                'sessions' => $sessions,
            ],
        ]);
    }

    public function edit(CashRegister $cashRegister): Response
    {
        $this->authorize('update', $cashRegister);

        return Inertia::render('admin/cash-registers/edit', [
            'cashRegister' => [
                'id' => $cashRegister->id,
                'name' => $cashRegister->name,
                'is_active' => $cashRegister->is_active,
            ],
        ]);
    }

    public function update(UpdateCashRegisterRequest $request, CashRegister $cashRegister): RedirectResponse
    {
        $this->authorize('update', $cashRegister);

        $cashRegister->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return to_route('admin.cash-registers.show', $cashRegister)
            ->with('toast', ['type' => 'success', 'message' => __('toast.cash_register_updated')]);
    }

    public function destroy(CashRegister $cashRegister): RedirectResponse
    {
        $this->authorize('delete', $cashRegister);

        if ($cashRegister->sessions()->where('status', 'open')->exists()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.cash_register_open_session'),
            ]);
        }

        if ($cashRegister->sessions()->exists()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.cash_register_has_sessions'),
            ]);
        }

        $cashRegister->delete();

        return to_route('admin.cash-registers.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.cash_register_deleted')]);
    }

    /** @return array<string, mixed> */
    private function transformSummary(CashRegister $register): array
    {
        $latestSession = $register->relationLoaded('sessions')
            ? $register->sessions->first()
            : null;

        return [
            'id' => $register->id,
            'name' => $register->name,
            'is_active' => $register->is_active,
            'sessions_count' => (int) ($register->sessions_count ?? 0),
            'open_sessions_count' => (int) ($register->open_sessions_count ?? 0),
            'latest_session' => $latestSession ? $this->transformSession($latestSession) : null,
            'created_at' => $register->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function transformSession(CashRegisterSession $session): array
    {
        return [
            'id' => $session->id,
            'status' => $session->status,
            'status_label' => $session->status === 'open' ? 'مفتوحة' : 'مغلقة',
            'session_date' => $session->session_date?->toDateString(),
            'opening_balance' => (float) $session->opening_balance,
            'expected_cash' => (float) $session->expected_cash,
            'actual_cash' => $session->actual_cash !== null ? (float) $session->actual_cash : null,
            'difference' => $session->difference !== null ? (float) $session->difference : null,
            'user_name' => $session->user?->name,
            'opened_at' => $session->opened_at?->toIso8601String(),
            'closed_at' => $session->closed_at?->toIso8601String(),
        ];
    }
}
