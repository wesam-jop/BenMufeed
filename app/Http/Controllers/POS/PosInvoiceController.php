<?php

namespace App\Http\Controllers\POS;

use App\Actions\UpdatePosInvoiceAction;
use App\Actions\VoidSaleAction;
use App\Enums\InvoiceStatus;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PosInvoiceController extends Controller
{
    public function edit(Request $request, Invoice $invoice): Response|RedirectResponse
    {
        $this->authorizeInvoice($request, $invoice);
        $this->ensurePermission($request, 'sales.update');

        if ($invoice->status === InvoiceStatus::Void) {
            return redirect()->route('pos.sessions.daily-summary')
                ->with('toast', ['type' => 'warning', 'message' => __('toast.invoice_void_locked')]);
        }

        $invoice->loadMissing(['items', 'payments', 'user']);
        $branchId = $request->user()->branch_id;

        $categories = ProductCategory::query()
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        $products = Product::query()
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->where('is_active', true)
            ->whereIn('type', [ProductType::Finished, ProductType::Combo, ProductType::Service])
            ->where('sale_price', '>', 0)
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'sale_price', 'image', 'category_id']);

        return Inertia::render('pos/invoice-edit', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status->value,
                'total' => (float) $invoice->total,
                'created_at' => $invoice->created_at?->toIso8601String(),
                'items' => $invoice->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                ]),
            ],
            'products' => $products,
            'categories' => $categories,
            'warehouseId' => $this->resolveWarehouseId($request),
        ]);
    }

    public function update(
        Request $request,
        Invoice $invoice,
        UpdatePosInvoiceAction $action,
    ): RedirectResponse {
        $this->authorizeInvoice($request, $invoice);
        $this->ensurePermission($request, 'sales.update');

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
        ], [
            'items.required' => 'يجب إدخال الأصناف.',
            'items.min' => 'يجب أن تحتوي الفاتورة على صنف واحد على الأقل.',
            'items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من صفر.',
        ]);

        try {
            $warehouse = Warehouse::query()
                ->where('id', $this->resolveWarehouseId($request))
                ->where('branch_id', $request->user()->branch_id)
                ->where('is_active', true)
                ->firstOrFail();

            $action->execute($invoice, $validated['items'], $warehouse, $request->user());

            return redirect()->route('pos.sessions.daily-summary')
                ->with('toast', ['type' => 'success', 'message' => __('toast.invoice_updated')]);
        } catch (RuntimeException $e) {
            return back()->with('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function print(Request $request, Invoice $invoice): Response|RedirectResponse
    {
        $this->authorizeInvoice($request, $invoice);
        $this->ensurePermission($request, 'sales.print_invoice');

        $invoice->loadMissing(['items', 'payments', 'user']);

        return Inertia::render('pos/invoice-print', [
            'invoice' => [
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status->value,
                'subtotal' => (float) $invoice->subtotal,
                'discount' => (float) $invoice->discount,
                'tax' => (float) $invoice->tax,
                'total' => (float) $invoice->total,
                'paid' => (float) $invoice->paid,
                'created_at' => $invoice->created_at?->toIso8601String(),
                'cashier_name' => $invoice->user?->name,
                'items' => $invoice->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total' => (float) $item->total,
                ]),
                'payment_method' => $invoice->payments->first()?->method?->value ?? 'cash',
            ],
            'currencyConversions' => app(\App\Services\Shared\CurrencyService::class)
                ->convertForReceipt((float) $invoice->total),
            'receiptTemplate' => app(\App\Services\Shared\BusinessSettingsService::class)->receiptTemplate(),
            'receiptBusiness' => app(\App\Services\Shared\BusinessSettingsService::class)->receiptBusiness(),
        ]);
    }

    public function destroy(
        Request $request,
        Invoice $invoice,
        VoidSaleAction $action,
    ): RedirectResponse {
        $this->authorizeInvoice($request, $invoice);
        $this->ensurePermission($request, 'sales.cancel');

        try {
            $action->execute($invoice, $request->user());

            return redirect()->route('pos.sessions.daily-summary')
                ->with('toast', ['type' => 'success', 'message' => __('toast.invoice_deleted')]);
        } catch (RuntimeException $e) {
            return back()->with('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function authorizeInvoice(Request $request, Invoice $invoice): void
    {
        abort_if($invoice->branch_id !== $request->user()->branch_id, 403);
    }

    private function ensurePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()->can($permission), 403);
    }

    private function resolveWarehouseId(Request $request): int
    {
        $branchId = $request->user()->branch_id;

        $warehouse = Warehouse::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('name', 'المستودع الرئيسي')
            ->first()
            ?? Warehouse::query()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

        if ($warehouse === null) {
            abort(422, 'لا يوجد مستودع نشط لهذا الفرع.');
        }

        return $warehouse->id;
    }
}
