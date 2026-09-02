<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Admin\System\AdminController;

use App\Actions\VoidSaleAction;
use App\Http\Requests\Admin\Sales\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Admin\Sales\Invoice\UpdateInvoiceRequest;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class InvoiceController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = $this->forBranch(Invoice::query(), $request)
            ->with(['user:id,name', 'order:id,order_number'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $invoices->getCollection()->transform(fn (Invoice $invoice) => [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status->value ?? $invoice->status,
            'total' => (float) $invoice->total,
            'paid' => (float) $invoice->paid,
            'created_at' => $invoice->created_at?->toIso8601String(),
            'cashier_name' => $invoice->user?->name,
            'order_number' => $invoice->order?->order_number,
        ]);

        return Inertia::render('admin/invoices/index', [
            'invoices' => $invoices,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Invoice::class);

        return Inertia::render('admin/invoices/create');
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        Invoice::create($request->validated());

        return to_route('admin.invoices.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.invoice_created')]);
    }

    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->loadMissing(['items', 'payments', 'user', 'order:id,order_number']);

        return Inertia::render('admin/invoices/show', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status->value ?? $invoice->status,
                'subtotal' => (float) $invoice->subtotal,
                'discount' => (float) $invoice->discount,
                'tax' => (float) $invoice->tax,
                'total' => (float) $invoice->total,
                'paid' => (float) $invoice->paid,
                'created_at' => $invoice->created_at?->toIso8601String(),
                'cashier_name' => $invoice->user?->name,
                'order_number' => $invoice->order?->order_number,
                'items' => $invoice->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) $item->total,
                ]),
            ],
        ]);
    }

    public function edit(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (($invoice->status->value ?? $invoice->status) === 'void') {
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('toast', ['type' => 'warning', 'message' => __('toast.invoice_void_locked')]);
        }

        return redirect()->route('pos.invoices.edit', $invoice);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $invoice->update($request->validated());

        return to_route('admin.invoices.show', $invoice)
            ->with('toast', ['type' => 'success', 'message' => __('toast.invoice_updated')]);
    }

    public function destroy(Invoice $invoice, VoidSaleAction $action): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        try {
            $action->execute($invoice, request()->user());

            return to_route('admin.invoices.index')
                ->with('toast', ['type' => 'success', 'message' => __('toast.invoice_deleted')]);
        } catch (RuntimeException $e) {
            return back()->with('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
