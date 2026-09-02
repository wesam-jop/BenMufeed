<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\System\AdminController;

use App\Http\Requests\Admin\Sales\Refund\StoreRefundRequest;
use App\Http\Requests\Admin\Sales\Refund\UpdateRefundRequest;
use App\Models\Refund;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefundController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Refund::class);

        $refunds = $this->forBranchThrough(Refund::query(), $request, 'invoice')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/refunds/index', [
            'refund' => $refunds,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Refund::class);

        return Inertia::render('admin/refunds/create');
    }

    public function store(StoreRefundRequest $request): RedirectResponse
    {
        $this->authorize('create', Refund::class);

        Refund::create($request->validated());

        return to_route('admin.refunds.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.created')]);
    }

    public function show(Refund $refund): Response
    {
        $this->authorize('view', $refund);

        return Inertia::render('admin/refunds/show', [
            'refund' => $refund,
        ]);
    }

    public function edit(Refund $refund): Response
    {
        $this->authorize('update', $refund);

        return Inertia::render('admin/refunds/edit', [
            'refund' => $refund,
        ]);
    }

    public function update(UpdateRefundRequest $request, Refund $refund): RedirectResponse
    {
        $this->authorize('update', $refund);

        $refund->update($request->validated());

        return to_route('admin.refunds.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.updated')]);
    }

    public function destroy(Refund $refund): RedirectResponse
    {
        $this->authorize('delete', $refund);

        $refund->delete();

        return to_route('admin.refunds.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.deleted')]);
    }
}
