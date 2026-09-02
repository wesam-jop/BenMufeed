<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\System\AdminController;

use App\Http\Requests\Admin\Sales\TableSession\StoreTableSessionRequest;
use App\Http\Requests\Admin\Sales\TableSession\UpdateTableSessionRequest;
use App\Models\TableSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TableSessionController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TableSession::class);

        $tableSessions = $this->forBranchThrough(TableSession::query(), $request, 'table')
            ->with('table:id,name,zone,capacity')
            ->latest('opened_at')
            ->paginate(15)
            ->withQueryString();

        $tableSessions->getCollection()->transform(fn (TableSession $session) => [
            'id' => $session->id,
            'status' => $session->status,
            'guest_count' => $session->guest_count,
            'opened_at' => $session->opened_at?->toIso8601String(),
            'closed_at' => $session->closed_at?->toIso8601String(),
            'table_name' => $session->table?->name,
            'table_zone' => $session->table?->zone,
        ]);

        return Inertia::render('admin/table-sessions/index', [
            'tableSessions' => $tableSessions,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TableSession::class);

        return Inertia::render('admin/table-sessions/create');
    }

    public function store(StoreTableSessionRequest $request): RedirectResponse
    {
        $this->authorize('create', TableSession::class);

        TableSession::create($request->validated());

        return to_route('admin.table-sessions.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.table_session_created')]);
    }

    public function show(TableSession $tableSession): Response
    {
        $this->authorize('view', $tableSession);

        $tableSession->loadMissing([
            'table:id,name,zone,capacity,status',
            'orders' => fn ($query) => $query->latest()->withCount('items'),
        ]);

        return Inertia::render('admin/table-sessions/show', [
            'tableSession' => [
                'id' => $tableSession->id,
                'status' => $tableSession->status,
                'guest_count' => $tableSession->guest_count,
                'opened_at' => $tableSession->opened_at?->toIso8601String(),
                'closed_at' => $tableSession->closed_at?->toIso8601String(),
                'table' => $tableSession->table ? [
                    'name' => $tableSession->table->name,
                    'zone' => $tableSession->table->zone,
                    'capacity' => $tableSession->table->capacity,
                    'status' => $tableSession->table->status,
                ] : null,
                'orders' => $tableSession->orders->map(fn ($order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status->value ?? $order->status,
                    'total' => (float) $order->total,
                    'items_count' => $order->items_count,
                    'created_at' => $order->created_at?->toIso8601String(),
                ])->values(),
            ],
        ]);
    }

    public function edit(TableSession $tableSession): Response
    {
        $this->authorize('update', $tableSession);

        return Inertia::render('admin/table-sessions/edit', [
            'tableSession' => [
                'id' => $tableSession->id,
                'guest_count' => $tableSession->guest_count,
            ],
        ]);
    }

    public function update(UpdateTableSessionRequest $request, TableSession $tableSession): RedirectResponse
    {
        $this->authorize('update', $tableSession);

        $tableSession->update($request->validated());

        return to_route('admin.table-sessions.show', $tableSession)
            ->with('toast', ['type' => 'success', 'message' => __('toast.table_session_updated')]);
    }

    public function destroy(TableSession $tableSession): RedirectResponse
    {
        $this->authorize('delete', $tableSession);

        $tableSession->delete();

        return to_route('admin.table-sessions.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.table_session_deleted')]);
    }
}
