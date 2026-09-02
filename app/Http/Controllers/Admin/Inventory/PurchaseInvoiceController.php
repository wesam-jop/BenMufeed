<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Admin\System\AdminController;

use App\Actions\ReceivePurchaseAction;
use App\Enums\ProductType;
use App\Enums\PurchaseInvoiceStatus;
use App\Http\Requests\Admin\Inventory\PurchaseInvoice\StorePurchaseInvoiceRequest;
use App\Http\Requests\Admin\Inventory\PurchaseInvoice\UpdatePurchaseInvoiceRequest;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Shared\NumberGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseInvoiceController extends AdminController
{
    public function __construct(
        private NumberGeneratorService $numberGenerator,
        private ReceivePurchaseAction $receivePurchaseAction,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseInvoice::class);

        $baseQuery = $this->forBranch(PurchaseInvoice::query(), $request)
            ->with([
                'supplier:id,name',
                'warehouse:id,name',
            ])
            ->withCount('items');

        $purchaseInvoices = (clone $baseQuery)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $purchaseInvoices->through(fn (PurchaseInvoice $invoice) => $this->transformSummary($invoice));

        $allInvoices = (clone $baseQuery)->get();

        return Inertia::render('admin/purchase-invoices/index', [
            'purchaseInvoices' => $purchaseInvoices,
            'summary' => $this->buildSummary($allInvoices),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', PurchaseInvoice::class);

        return Inertia::render('admin/purchase-invoices/create', [
            'suppliers' => Supplier::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'warehouses' => $this->forBranch(Warehouse::query(), $request)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StorePurchaseInvoiceRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseInvoice::class);

        $invoice = PurchaseInvoice::create([
            'branch_id' => $this->branchId($request) ?? $request->user()->branch_id,
            'supplier_id' => $request->validated('supplier_id'),
            'warehouse_id' => $request->validated('warehouse_id'),
            'invoice_number' => $request->validated('invoice_number')
                ?? $this->numberGenerator->next('PI', PurchaseInvoice::class, 'invoice_number'),
            'status' => PurchaseInvoiceStatus::Draft,
            'due_date' => $request->validated('due_date'),
            'user_id' => $request->user()->id,
        ]);

        return to_route('admin.purchase-invoices.show', $invoice)
            ->with('toast', ['type' => 'success', 'message' => __('toast.purchase_invoice_created')]);
    }

    public function show(PurchaseInvoice $purchaseInvoice): Response
    {
        $this->authorize('view', $purchaseInvoice);

        $purchaseInvoice->load([
            'supplier:id,name',
            'warehouse:id,name',
            'user:id,name',
            'items.product.unit:id,name,abbreviation',
        ]);

        return Inertia::render('admin/purchase-invoices/show', [
            'purchaseInvoice' => $this->transformDetail($purchaseInvoice),
            'availableMaterials' => $this->availableMaterials($purchaseInvoice->branch_id),
        ]);
    }

    public function edit(PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        $this->authorize('update', $purchaseInvoice);

        return to_route('admin.purchase-invoices.show', $purchaseInvoice);
    }

    public function update(UpdatePurchaseInvoiceRequest $request, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        $this->authorize('update', $purchaseInvoice);

        abort_unless($purchaseInvoice->status === PurchaseInvoiceStatus::Draft, 422, 'لا يمكن تعديل فاتورة مستلمة.');

        DB::transaction(function () use ($request, $purchaseInvoice) {
            $this->syncItems(
                $purchaseInvoice,
                $request->validated('items'),
                $request->has('tax') ? (float) $request->validated('tax') : null,
            );
        });

        return to_route('admin.purchase-invoices.show', $purchaseInvoice)
            ->with('toast', ['type' => 'success', 'message' => __('toast.purchase_items_saved')]);
    }

    public function receive(Request $request, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        $this->authorize('update', $purchaseInvoice);

        $this->receivePurchaseAction->execute($purchaseInvoice, $request->user());

        return to_route('admin.purchase-invoices.show', $purchaseInvoice)
            ->with('toast', ['type' => 'success', 'message' => __('toast.purchase_received')]);
    }

    public function destroy(PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        $this->authorize('delete', $purchaseInvoice);

        abort_unless($purchaseInvoice->status === PurchaseInvoiceStatus::Draft, 422, 'لا يمكن حذف فاتورة مستلمة.');

        $purchaseInvoice->delete();

        return to_route('admin.purchase-invoices.index')
            ->with('toast', ['type' => 'success', 'message' => __('toast.purchase_invoice_deleted')]);
    }

    /** @return list<array<string, mixed>> */
    private function availableMaterials(int $branchId): array
    {
        return Product::query()
            ->where('branch_id', $branchId)
            ->whereIn('type', [ProductType::Raw, ProductType::SemiFinished])
            ->where('track_stock', true)
            ->where('is_active', true)
            ->with('unit:id,name,abbreviation')
            ->orderBy('name')
            ->get(['id', 'name', 'cost_price', 'sku', 'unit_id'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'cost_price' => (float) $product->cost_price,
                'unit' => $product->unit ? [
                    'name' => $product->unit->name,
                    'abbreviation' => $product->unit->abbreviation,
                ] : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{id?: int, product_id: int, quantity: float|int|string, unit_price: float|int|string}>  $items
     */
    private function syncItems(PurchaseInvoice $invoice, array $items, ?float $tax = null): void
    {
        $keepIds = [];
        $subtotal = 0.0;

        foreach ($items as $itemData) {
            $quantity = (float) $itemData['quantity'];
            $unitPrice = (float) $itemData['unit_price'];
            $lineTotal = round($quantity * $unitPrice, 2);
            $subtotal += $lineTotal;

            if (! empty($itemData['id'])) {
                $item = PurchaseInvoiceItem::query()
                    ->where('purchase_invoice_id', $invoice->id)
                    ->findOrFail($itemData['id']);

                $item->update([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                ]);

                $keepIds[] = $item->id;

                continue;
            }

            $item = PurchaseInvoiceItem::create([
                'purchase_invoice_id' => $invoice->id,
                'product_id' => $itemData['product_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $lineTotal,
            ]);

            $keepIds[] = $item->id;
        }

        PurchaseInvoiceItem::query()
            ->where('purchase_invoice_id', $invoice->id)
            ->whereNotIn('id', $keepIds)
            ->delete();

        $taxAmount = $tax ?? (float) $invoice->tax;
        $total = round($subtotal + $taxAmount, 2);

        $invoice->update([
            'subtotal' => round($subtotal, 2),
            'tax' => $taxAmount,
            'total' => $total,
            'balance' => $total - (float) $invoice->paid,
        ]);
    }

    /** @return array<string, int|float> */
    private function buildSummary($invoices): array
    {
        return [
            'invoices_count' => $invoices->count(),
            'draft_count' => $invoices->where('status', PurchaseInvoiceStatus::Draft)->count(),
            'posted_count' => $invoices->filter(
                fn (PurchaseInvoice $invoice) => in_array($invoice->status, [
                    PurchaseInvoiceStatus::Posted,
                    PurchaseInvoiceStatus::Paid,
                    PurchaseInvoiceStatus::Partial,
                ], true),
            )->count(),
            'total_value' => round((float) $invoices->sum('total'), 2),
        ];
    }

    /** @return array<string, mixed> */
    private function transformSummary(PurchaseInvoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'supplier_name' => $invoice->supplier?->name ?? '—',
            'warehouse_name' => $invoice->warehouse?->name ?? '—',
            'status' => $invoice->status->value,
            'status_label' => $this->statusLabel($invoice->status),
            'items_count' => (int) ($invoice->items_count ?? $invoice->items->count()),
            'total' => (float) $invoice->total,
            'created_at' => $invoice->created_at?->toIso8601String(),
            'can_edit' => $invoice->status === PurchaseInvoiceStatus::Draft,
        ];
    }

    /** @return array<string, mixed> */
    private function transformDetail(PurchaseInvoice $invoice): array
    {
        $items = $invoice->items->map(function (PurchaseInvoiceItem $item) {
            $quantity = (float) $item->quantity;
            $unitPrice = (float) $item->unit_price;

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '—',
                'product_sku' => $item->product?->sku,
                'unit' => $item->product?->unit ? [
                    'name' => $item->product->unit->name,
                    'abbreviation' => $item->product->unit->abbreviation,
                ] : null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_value' => round((float) $item->total, 2),
            ];
        })->values()->all();

        return [
            ...$this->transformSummary($invoice),
            'subtotal' => (float) $invoice->subtotal,
            'tax' => (float) $invoice->tax,
            'paid' => (float) $invoice->paid,
            'balance' => (float) $invoice->balance,
            'due_date' => $invoice->due_date?->toDateString(),
            'user_name' => $invoice->user?->name,
            'items' => $items,
            'summary' => [
                'items_count' => count($items),
                'total_quantity' => round(collect($items)->sum('quantity'), 4),
                'subtotal' => round(collect($items)->sum('line_value'), 2),
            ],
        ];
    }

    private function statusLabel(PurchaseInvoiceStatus $status): string
    {
        return match ($status) {
            PurchaseInvoiceStatus::Draft => 'مسودة',
            PurchaseInvoiceStatus::Posted => 'مُستلَم',
            PurchaseInvoiceStatus::Paid => 'مدفوعة',
            PurchaseInvoiceStatus::Partial => 'مدفوعة جزئياً',
            PurchaseInvoiceStatus::Cancelled => 'ملغاة',
        };
    }
}
