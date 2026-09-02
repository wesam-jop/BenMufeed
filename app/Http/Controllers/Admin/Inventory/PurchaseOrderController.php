<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Admin\System\AdminController;

use App\Http\Requests\Admin\Inventory\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\Admin\Inventory\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $purchaseOrders = $this->forBranch(PurchaseOrder::query(), $request)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/purchase-orders/index', [
            'purchaseOrder' => $purchaseOrders,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PurchaseOrder::class);

        return Inertia::render('admin/purchase-orders/create');
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        PurchaseOrder::create($request->validated());

        return to_route('admin.purchase-orders.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.created')]);
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('view', $purchaseOrder);

        return Inertia::render('admin/purchase-orders/show', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('update', $purchaseOrder);

        return Inertia::render('admin/purchase-orders/edit', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('update', $purchaseOrder);

        $purchaseOrder->update($request->validated());

        return to_route('admin.purchase-orders.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.updated')]);
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('delete', $purchaseOrder);

        $purchaseOrder->delete();

        return to_route('admin.purchase-orders.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.deleted')]);
    }
}
