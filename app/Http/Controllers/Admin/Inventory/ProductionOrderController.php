<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Admin\System\AdminController;

use App\Http\Requests\Admin\Inventory\ProductionOrder\StoreProductionOrderRequest;
use App\Http\Requests\Admin\Inventory\ProductionOrder\UpdateProductionOrderRequest;
use App\Models\ProductionOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductionOrderController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProductionOrder::class);

        $productionOrders = $this->forBranch(ProductionOrder::query(), $request)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/production-orders/index', [
            'productionOrder' => $productionOrders,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ProductionOrder::class);

        return Inertia::render('admin/production-orders/create');
    }

    public function store(StoreProductionOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', ProductionOrder::class);

        ProductionOrder::create($request->validated());

        return to_route('admin.production-orders.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.created')]);
    }

    public function show(ProductionOrder $productionOrder): Response
    {
        $this->authorize('view', $productionOrder);

        return Inertia::render('admin/production-orders/show', [
            'productionOrder' => $productionOrder,
        ]);
    }

    public function edit(ProductionOrder $productionOrder): Response
    {
        $this->authorize('update', $productionOrder);

        return Inertia::render('admin/production-orders/edit', [
            'productionOrder' => $productionOrder,
        ]);
    }

    public function update(UpdateProductionOrderRequest $request, ProductionOrder $productionOrder): RedirectResponse
    {
        $this->authorize('update', $productionOrder);

        $productionOrder->update($request->validated());

        return to_route('admin.production-orders.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.updated')]);
    }

    public function destroy(ProductionOrder $productionOrder): RedirectResponse
    {
        $this->authorize('delete', $productionOrder);

        $productionOrder->delete();

        return to_route('admin.production-orders.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.deleted')]);
    }
}
