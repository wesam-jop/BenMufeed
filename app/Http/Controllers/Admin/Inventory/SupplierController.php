<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Admin\System\AdminController;

use App\Http\Requests\Admin\Inventory\Supplier\StoreSupplierRequest;
use App\Http\Requests\Admin\Inventory\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Supplier::class);

        $baseQuery = Supplier::query()
            ->withCount(['purchaseInvoices']);

        $suppliers = (clone $baseQuery)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $suppliers->through(fn (Supplier $supplier) => $this->transformSummary($supplier));

        $allSuppliers = (clone $baseQuery)->get();

        return Inertia::render('admin/suppliers/index', [
            'suppliers' => $suppliers,
            'summary' => $this->buildSummary($allSuppliers),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Supplier::class);

        return Inertia::render('admin/suppliers/create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $this->authorize('create', Supplier::class);

        $supplier = Supplier::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return to_route('admin.suppliers.show', $supplier)
            ->with('toast', ['type' => 'success', 'message' => __('toast.supplier_created')]);
    }

    public function show(Supplier $supplier): Response
    {
        $this->authorize('view', $supplier);

        $supplier->loadCount(['purchaseInvoices', 'payments']);

        return Inertia::render('admin/suppliers/show', [
            'supplier' => $this->transformDetail($supplier),
        ]);
    }

    public function edit(Supplier $supplier): Response
    {
        $this->authorize('update', $supplier);

        return Inertia::render('admin/suppliers/edit', [
            'supplier' => $this->transformDetail($supplier),
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $supplier->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return to_route('admin.suppliers.show', $supplier)
            ->with('toast', ['type' => 'success', 'message' => __('toast.supplier_updated')]);
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        if ($supplier->purchaseOrders()->exists() || $supplier->purchaseInvoices()->exists()) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.supplier_has_docs'),
            ]);
        }

        $supplier->delete();

        return to_route('admin.suppliers.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.supplier_deleted')]);
    }

    /** @return array<string, int|float> */
    private function buildSummary($suppliers): array
    {
        return [
            'suppliers_count' => $suppliers->count(),
            'active_count' => $suppliers->where('is_active', true)->count(),
            'total_balance' => round((float) $suppliers->sum('balance'), 2),
        ];
    }

    /** @return array<string, mixed> */
    private function transformSummary(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'contact_person' => $supplier->contact_person,
            'phone' => $supplier->phone,
            'email' => $supplier->email,
            'balance' => (float) $supplier->balance,
            'is_active' => $supplier->is_active,
            'purchase_invoices_count' => (int) ($supplier->purchase_invoices_count ?? 0),
            'created_at' => $supplier->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function transformDetail(Supplier $supplier): array
    {
        return [
            ...$this->transformSummary($supplier),
            'address' => $supplier->address,
            'tax_number' => $supplier->tax_number,
            'payments_count' => (int) ($supplier->payments_count ?? 0),
        ];
    }
}
