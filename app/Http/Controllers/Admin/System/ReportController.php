<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\System\AdminController;

use App\Admin\Report;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\WarehouseType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends AdminController
{
    public function sales(Request $request): Response
    {
        $this->authorize('sales', Report::class);

        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        $baseQuery = $this->salesReportQuery($request, $from, $to);

        $invoices = (clone $baseQuery)
            ->with([
                'user:id,name',
                'customer:id,name,phone',
                'order:id,order_number,source',
                'payments:id,invoice_id,method,amount',
            ])
            ->withCount('items')
            ->latest('issued_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $invoices->through(fn (Invoice $invoice) => $this->transformSalesInvoice($invoice));

        $allInvoices = (clone $baseQuery)->with('payments')->get();
        $activeInvoiceIds = $allInvoices
            ->where('status', '!=', InvoiceStatus::Void)
            ->pluck('id');

        return Inertia::render('admin/reports/sales', [
            'invoices' => $invoices,
            'summary' => $this->buildSalesSummary($allInvoices, $activeInvoiceIds),
            'topProducts' => $this->topSellingProducts($activeInvoiceIds),
            'dailyBreakdown' => $this->dailySalesBreakdown($baseQuery),
            'paymentBreakdown' => $this->paymentMethodBreakdown($allInvoices),
            'filters' => [
                'from' => $from,
                'to' => $to,
                'status' => $request->input('status', ''),
                'payment_method' => $request->input('payment_method', ''),
                'user_id' => $request->input('user_id', ''),
                'search' => $request->input('search', ''),
            ],
            'filterOptions' => [
                'statuses' => $this->invoiceStatusOptions(),
                'paymentMethods' => $this->paymentMethodOptions(),
                'cashiers' => $this->cashierOptions($request),
            ],
        ]);
    }

    public function products(Request $request): Response
    {
        $this->authorize('products', Report::class);

        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();
        $sort = $request->input('sort', 'revenue');

        $baseQuery = $this->productsReportBaseQuery($request, $from, $to);

        $items = $this->productsAggregateQuery($baseQuery, $sort)
            ->paginate(20)
            ->withQueryString();

        $productMap = Product::query()
            ->with('category:id,name')
            ->whereIn('id', collect($items->items())->pluck('product_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $items->through(fn ($row) => $this->transformProductReportRow($row, $productMap));

        $allRows = $this->productsAggregateQuery($baseQuery, $sort)->get();

        return Inertia::render('admin/reports/products', [
            'items' => $items,
            'summary' => $this->buildProductsSummary($allRows),
            'categoryBreakdown' => $this->productsCategoryBreakdown($baseQuery),
            'filters' => [
                'from' => $from,
                'to' => $to,
                'search' => $request->input('search', ''),
                'sort' => $sort,
            ],
            'filterOptions' => [
                'sorts' => [
                    ['value' => 'revenue', 'label' => 'الإيراد'],
                    ['value' => 'quantity', 'label' => 'الكمية'],
                    ['value' => 'profit', 'label' => 'الربح'],
                    ['value' => 'margin', 'label' => 'هامش الربح'],
                ],
            ],
        ]);
    }

    public function inventory(Request $request): Response
    {
        $this->authorize('inventory', Report::class);

        $baseQuery = $this->inventoryReportBaseQuery($request);

        $balances = (clone $baseQuery)
            ->with([
                'product:id,name,sku,cost_price,min_stock,max_stock,unit_id,is_active',
                'product.unit:id,name,abbreviation',
                'warehouse:id,name,type,is_active',
            ])
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->paginate(25)
            ->withQueryString();

        $balances->through(fn (StockBalance $balance) => $this->transformInventoryRow($balance));

        $allBalances = (clone $baseQuery)
            ->with(['product:id,cost_price,min_stock', 'warehouse:id,name'])
            ->get();

        return Inertia::render('admin/reports/inventory', [
            'balances' => $balances,
            'summary' => $this->buildInventorySummary($allBalances),
            'warehouseBreakdown' => $this->inventoryWarehouseBreakdown($allBalances),
            'filters' => [
                'warehouse_id' => $request->input('warehouse_id', ''),
                'search' => $request->input('search', ''),
                'stock_status' => $request->input('stock_status', 'in_stock'),
            ],
            'filterOptions' => [
                'warehouses' => $this->warehouseFilterOptions($request),
                'stockStatuses' => $this->stockStatusOptions(),
            ],
        ]);
    }

    public function customers(Request $request): Response
    {
        $this->authorize('customers', Report::class);

        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();
        $sort = $request->input('sort', 'revenue');

        $baseQuery = $this->customersReportBaseQuery($request, $from, $to, $sort);

        $customers = (clone $baseQuery)
            ->paginate(20)
            ->withQueryString();

        $customers->through(fn (Customer $customer) => $this->transformCustomerReportRow($customer));

        $allCustomers = (clone $baseQuery)->get();

        return Inertia::render('admin/reports/customers', [
            'customers' => $customers,
            'summary' => $this->buildCustomersSummary($allCustomers),
            'topCustomers' => $this->topCustomersBreakdown($allCustomers),
            'filters' => [
                'from' => $from,
                'to' => $to,
                'search' => $request->input('search', ''),
                'status' => $request->input('status', 'all'),
                'activity' => $request->input('activity', 'with_sales'),
                'sort' => $sort,
            ],
            'filterOptions' => [
                'statuses' => $this->customerStatusOptions(),
                'activities' => $this->customerActivityOptions(),
                'sorts' => $this->customerSortOptions(),
            ],
        ]);
    }

    private function salesReportQuery(Request $request, string $from, string $to): Builder
    {
        $query = $this->forBranch(Invoice::query(), $request)
            ->whereDate('issued_at', '>=', $from)
            ->whereDate('issued_at', '<=', $to);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $customerQuery) => $customerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') !== 'all') {
                $query->where('status', $request->input('status'));
            }
        } else {
            $query->where('status', '!=', InvoiceStatus::Void->value);
        }

        if ($request->filled('payment_method')) {
            $query->whereHas(
                'payments',
                fn (Builder $paymentQuery) => $paymentQuery->where('method', $request->input('payment_method')),
            );
        }

        return $query;
    }

    private function transformSalesInvoice(Invoice $invoice): array
    {
        $payments = $invoice->payments
            ->map(fn (Payment $payment) => [
                'method' => $payment->method->value,
                'method_label' => $this->paymentMethodLabel($payment->method),
                'amount' => (float) $payment->amount,
            ])
            ->values()
            ->all();

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status->value,
            'status_label' => $this->invoiceStatusLabel($invoice->status),
            'subtotal' => (float) $invoice->subtotal,
            'discount' => (float) $invoice->discount,
            'tax' => (float) $invoice->tax,
            'total' => (float) $invoice->total,
            'paid' => (float) $invoice->paid,
            'balance' => (float) $invoice->balance,
            'items_count' => (int) ($invoice->items_count ?? 0),
            'issued_at' => $invoice->issued_at?->toIso8601String(),
            'cashier_name' => $invoice->user?->name,
            'customer_name' => $invoice->customer?->name,
            'customer_phone' => $invoice->customer?->phone,
            'order_number' => $invoice->order?->order_number,
            'order_source' => $invoice->order?->source,
            'payments' => $payments,
            'payment_methods_label' => collect($payments)
                ->pluck('method_label')
                ->unique()
                ->implode(' · '),
        ];
    }

    /** @param  Collection<int, Invoice>  $invoices */
    private function buildSalesSummary(Collection $invoices, Collection $invoiceIds): array
    {
        $active = $invoices->where('status', '!=', InvoiceStatus::Void);
        $voided = $invoices->where('status', InvoiceStatus::Void);

        $itemsSold = $invoiceIds->isEmpty()
            ? 0.0
            : (float) InvoiceItem::query()->whereIn('invoice_id', $invoiceIds)->sum('quantity');

        $totalCost = $invoiceIds->isEmpty()
            ? 0.0
            : (float) InvoiceItem::query()
                ->whereIn('invoice_id', $invoiceIds)
                ->selectRaw('SUM(cost_price * quantity) as total')
                ->value('total');

        $totalSales = round((float) $active->sum('total'), 2);
        $grossProfit = round($totalSales - $totalCost, 2);

        return [
            'invoices_count' => $active->count(),
            'void_count' => $voided->count(),
            'total_sales' => $totalSales,
            'total_paid' => round((float) $active->sum('paid'), 2),
            'total_tax' => round((float) $active->sum('tax'), 2),
            'total_discount' => round((float) $active->sum('discount'), 2),
            'average_ticket' => $active->count() > 0
                ? round((float) $active->avg('total'), 2)
                : 0.0,
            'items_sold' => round($itemsSold, 2),
            'total_cost' => round($totalCost, 2),
            'gross_profit' => $grossProfit,
        ];
    }

    /** @return list<array{product_id: int|null, product_name: string, quantity: float, revenue: float}> */
    private function topSellingProducts(Collection $invoiceIds): array
    {
        if ($invoiceIds->isEmpty()) {
            return [];
        }

        return InvoiceItem::query()
            ->selectRaw('product_id, product_name, SUM(quantity) as quantity, SUM(total) as revenue')
            ->whereIn('invoice_id', $invoiceIds)
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'quantity' => round((float) $row->quantity, 2),
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{date: string, invoices_count: int, total: float}> */
    private function dailySalesBreakdown(Builder $baseQuery): array
    {
        return (clone $baseQuery)
            ->where('status', '!=', InvoiceStatus::Void->value)
            ->selectRaw('DATE(issued_at) as sale_date, COUNT(*) as invoices_count, SUM(total) as total')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->sale_date,
                'invoices_count' => (int) $row->invoices_count,
                'total' => round((float) $row->total, 2),
            ])
            ->values()
            ->all();
    }

    /** @param  Collection<int, Invoice>  $invoices */
    private function paymentMethodBreakdown(Collection $invoices): array
    {
        return $invoices
            ->where('status', '!=', InvoiceStatus::Void)
            ->flatMap(fn (Invoice $invoice) => $invoice->payments)
            ->groupBy(fn (Payment $payment) => $payment->method->value)
            ->map(fn (Collection $payments, string $method) => [
                'method' => $method,
                'method_label' => $this->paymentMethodLabel(PaymentMethod::from($method)),
                'transactions_count' => $payments->count(),
                'total' => round((float) $payments->sum('amount'), 2),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /** @return list<array{value: string, label: string}> */
    private function invoiceStatusOptions(): array
    {
        return collect([
            ['value' => '', 'label' => 'بدون ملغاة'],
            ['value' => 'all', 'label' => 'كل الحالات'],
            ...collect(InvoiceStatus::cases())->map(fn (InvoiceStatus $status) => [
                'value' => $status->value,
                'label' => $this->invoiceStatusLabel($status),
            ]),
        ])->values()->all();
    }

    /** @return list<array{value: string, label: string}> */
    private function paymentMethodOptions(): array
    {
        return collect(PaymentMethod::cases())
            ->map(fn (PaymentMethod $method) => [
                'value' => $method->value,
                'label' => $this->paymentMethodLabel($method),
            ])
            ->prepend(['value' => '', 'label' => 'كل طرق الدفع'])
            ->values()
            ->all();
    }

    /** @return list<array{id: int, name: string}> */
    private function cashierOptions(Request $request): array
    {
        return User::query()
            ->select(['users.id', 'users.name'])
            ->whereHas('invoices', function (Builder $query) use ($request) {
                $branchId = $this->branchId($request);
                if ($branchId !== null) {
                    $query->where('branch_id', $branchId);
                }
            })
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
            ->values()
            ->all();
    }

    private function invoiceStatusLabel(InvoiceStatus $status): string
    {
        return match ($status) {
            InvoiceStatus::Draft => 'مسودة',
            InvoiceStatus::Issued => 'صادرة',
            InvoiceStatus::Paid => 'مدفوعة',
            InvoiceStatus::Partial => 'مدفوعة جزئياً',
            InvoiceStatus::Void => 'ملغاة',
            InvoiceStatus::Overdue => 'متأخرة',
        };
    }

    private function paymentMethodLabel(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::Cash => 'نقدي',
            PaymentMethod::Card => 'بطاقة',
            PaymentMethod::BankTransfer => 'تحويل بنكي',
            PaymentMethod::Wallet => 'محفظة',
            PaymentMethod::Loyalty => 'ولاء',
            PaymentMethod::Other => 'أخرى',
        };
    }

    private function productsReportBaseQuery(Request $request, string $from, string $to): Builder
    {
        $branchId = $this->branchId($request);

        $query = InvoiceItem::query()
            ->whereHas('invoice', function (Builder $invoiceQuery) use ($branchId, $from, $to) {
                if ($branchId !== null) {
                    $invoiceQuery->where('branch_id', $branchId);
                }

                $invoiceQuery
                    ->whereDate('issued_at', '>=', $from)
                    ->whereDate('issued_at', '<=', $to)
                    ->where('status', '!=', InvoiceStatus::Void->value);
            });

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('product', fn (Builder $productQuery) => $productQuery
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    private function productsAggregateQuery(Builder $baseQuery, string $sort): Builder
    {
        $query = (clone $baseQuery)
            ->selectRaw('product_id, product_name, SUM(quantity) as total_qty, SUM(total) as total_revenue, SUM(cost_price * quantity) as total_cost, COUNT(DISTINCT invoice_id) as invoices_count')
            ->groupBy('product_id', 'product_name');

        return match ($sort) {
            'quantity' => $query->orderByDesc('total_qty'),
            'profit' => $query->orderByRaw('(SUM(total) - SUM(cost_price * quantity)) DESC'),
            'margin' => $query->orderByRaw('CASE WHEN SUM(total) > 0 THEN (SUM(total) - SUM(cost_price * quantity)) / SUM(total) ELSE 0 END DESC'),
            default => $query->orderByDesc('total_revenue'),
        };
    }

    /** @param  Collection<int, Product>  $productMap */
    private function transformProductReportRow(object $row, Collection $productMap): array
    {
        $product = $row->product_id ? $productMap->get($row->product_id) : null;
        $revenue = round((float) $row->total_revenue, 2);
        $cost = round((float) $row->total_cost, 2);
        $quantity = round((float) $row->total_qty, 2);
        $profit = round($revenue - $cost, 2);

        return [
            'product_id' => $row->product_id,
            'product_name' => $row->product_name,
            'sku' => $product?->sku,
            'category_name' => $product?->category?->name,
            'product_type' => $product?->type?->value,
            'quantity' => $quantity,
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $profit,
            'margin_percent' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0.0,
            'invoices_count' => (int) $row->invoices_count,
            'avg_unit_price' => $quantity > 0 ? round($revenue / $quantity, 2) : 0.0,
            'list_price' => $product !== null ? (float) $product->sale_price : null,
        ];
    }

    /** @param  Collection<int, object>  $rows */
    private function buildProductsSummary(Collection $rows): array
    {
        $revenue = round((float) $rows->sum('total_revenue'), 2);
        $cost = round((float) $rows->sum('total_cost'), 2);
        $quantity = round((float) $rows->sum('total_qty'), 2);
        $profit = round($revenue - $cost, 2);

        return [
            'products_count' => $rows->count(),
            'total_quantity' => $quantity,
            'total_revenue' => $revenue,
            'total_cost' => $cost,
            'gross_profit' => $profit,
            'average_margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0.0,
            'average_unit_price' => $quantity > 0 ? round($revenue / $quantity, 2) : 0.0,
        ];
    }

    /** @return list<array{category_name: string, quantity: float, revenue: float, products_count: int}> */
    private function productsCategoryBreakdown(Builder $baseQuery): array
    {
        return (clone $baseQuery)
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->selectRaw("
                COALESCE(product_categories.name, 'بدون تصنيف') as category_name,
                COUNT(DISTINCT invoice_items.product_id) as products_count,
                SUM(invoice_items.quantity) as quantity,
                SUM(invoice_items.total) as revenue
            ")
            ->groupBy('product_categories.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'category_name' => $row->category_name,
                'products_count' => (int) $row->products_count,
                'quantity' => round((float) $row->quantity, 2),
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->values()
            ->all();
    }

    private function inventoryReportBaseQuery(Request $request): Builder
    {
        $branchId = $this->branchId($request);
        $stockStatus = $request->input('stock_status', 'in_stock');

        $query = StockBalance::query()
            ->when($branchId, fn (Builder $q) => $q->whereHas(
                'warehouse',
                fn (Builder $warehouseQuery) => $warehouseQuery->where('branch_id', $branchId),
            ));

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('product', fn (Builder $productQuery) => $productQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }

        match ($stockStatus) {
            'all' => null,
            'out' => $query->where('quantity', '<=', 0),
            'negative' => $query->where('quantity', '<', 0),
            'low' => $query->where('quantity', '>', 0)->whereHas(
                'product',
                fn (Builder $productQuery) => $productQuery
                    ->where('min_stock', '>', 0)
                    ->whereColumn('stock_balances.quantity', '<=', 'products.min_stock'),
            ),
            default => $query->where('quantity', '>', 0),
        };

        return $query;
    }

    private function transformInventoryRow(StockBalance $balance): array
    {
        $product = $balance->product;
        $warehouse = $balance->warehouse;
        $quantity = round((float) $balance->quantity, 4);
        $unitCost = round((float) ($balance->avg_cost ?: $product?->cost_price ?: 0), 4);
        $totalValue = round($quantity * $unitCost, 2);
        $minStock = round((float) ($product?->min_stock ?? 0), 4);
        $stockStatus = $this->resolveStockStatus($quantity, $minStock);

        return [
            'id' => $balance->id,
            'product_id' => $balance->product_id,
            'warehouse_id' => $balance->warehouse_id,
            'product_name' => $product?->name ?? '—',
            'sku' => $product?->sku,
            'unit' => $product?->unit?->abbreviation ?? $product?->unit?->name,
            'warehouse_name' => $warehouse?->name ?? '—',
            'warehouse_type' => $warehouse?->type?->value,
            'warehouse_type_label' => $warehouse?->type ? $this->warehouseTypeLabel($warehouse->type) : null,
            'quantity' => $quantity,
            'avg_cost' => $unitCost,
            'total_value' => $totalValue,
            'min_stock' => $minStock,
            'max_stock' => $product?->max_stock !== null ? round((float) $product->max_stock, 4) : null,
            'is_active' => (bool) ($product?->is_active ?? true),
            'stock_status' => $stockStatus,
            'stock_status_label' => $this->stockStatusLabel($stockStatus),
            'updated_at' => $balance->updated_at?->toIso8601String(),
        ];
    }

    /** @param  Collection<int, StockBalance>  $balances */
    private function buildInventorySummary(Collection $balances): array
    {
        $rows = $balances->map(fn (StockBalance $balance) => $this->transformInventoryRow($balance));

        return [
            'materials_count' => $rows->count(),
            'warehouses_count' => $balances->pluck('warehouse_id')->unique()->count(),
            'total_quantity' => round((float) $rows->sum('quantity'), 2),
            'total_value' => round((float) $rows->sum('total_value'), 2),
            'low_stock_count' => $rows->where('stock_status', 'low')->count(),
            'out_of_stock_count' => $rows->where('stock_status', 'out')->count(),
            'negative_stock_count' => $rows->where('stock_status', 'negative')->count(),
        ];
    }

    /** @param  Collection<int, StockBalance>  $balances */
    private function inventoryWarehouseBreakdown(Collection $balances): array
    {
        return $balances
            ->groupBy('warehouse_id')
            ->map(function (Collection $group) {
                $warehouse = $group->first()->warehouse;
                $rows = $group->map(fn (StockBalance $balance) => $this->transformInventoryRow($balance));

                return [
                    'warehouse_id' => $warehouse?->id,
                    'warehouse_name' => $warehouse?->name ?? '—',
                    'materials_count' => $rows->count(),
                    'total_quantity' => round((float) $rows->sum('quantity'), 2),
                    'total_value' => round((float) $rows->sum('total_value'), 2),
                    'low_stock_count' => $rows->where('stock_status', 'low')->count(),
                ];
            })
            ->sortByDesc('total_value')
            ->values()
            ->all();
    }

    /** @return list<array{id: int, name: string}> */
    private function warehouseFilterOptions(Request $request): array
    {
        $branchId = $this->branchId($request);

        return Warehouse::query()
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Warehouse $warehouse) => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{value: string, label: string}> */
    private function stockStatusOptions(): array
    {
        return [
            ['value' => 'in_stock', 'label' => 'متوفر فقط'],
            ['value' => 'all', 'label' => 'كل الأرصدة'],
            ['value' => 'low', 'label' => 'مخزون منخفض'],
            ['value' => 'out', 'label' => 'نفد / صفر'],
            ['value' => 'negative', 'label' => 'سالب'],
        ];
    }

    private function resolveStockStatus(float $quantity, float $minStock): string
    {
        if ($quantity < 0) {
            return 'negative';
        }

        if ($quantity <= 0) {
            return 'out';
        }

        if ($minStock > 0 && $quantity <= $minStock) {
            return 'low';
        }

        return 'ok';
    }

    private function stockStatusLabel(string $status): string
    {
        return match ($status) {
            'ok' => 'متوفر',
            'low' => 'منخفض',
            'out' => 'نفد',
            'negative' => 'سالب',
            default => $status,
        };
    }

    private function warehouseTypeLabel(WarehouseType $type): string
    {
        return match ($type) {
            WarehouseType::Main => 'رئيسي',
            WarehouseType::Kitchen => 'مطبخ',
            WarehouseType::ColdStorage => 'تبريد',
            WarehouseType::Retail => 'بيع',
            WarehouseType::Production => 'إنتاج',
        };
    }

    private function customersReportBaseQuery(Request $request, string $from, string $to, string $sort): Builder
    {
        $invoiceScope = fn (Builder $query) => $this->scopedCustomerInvoices($query, $request, $from, $to);
        $orderScope = fn (Builder $query) => $this->scopedCustomerOrders($query, $request, $from, $to);
        $lastPurchaseScope = fn (Builder $query) => $this->scopedCustomerInvoices($query, $request);

        $query = Customer::query()
            ->withCount(['invoices as invoices_count' => $invoiceScope])
            ->withSum(['invoices as total_spent' => $invoiceScope], 'total')
            ->withCount(['orders as orders_count' => $orderScope])
            ->withMax(['invoices as last_purchase_at' => $lastPurchaseScope], 'issued_at');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        match ($request->input('status', 'all')) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            default => null,
        };

        match ($request->input('activity', 'with_sales')) {
            'with_sales' => $query->whereHas('invoices', $invoiceScope),
            'no_sales' => $query->whereDoesntHave('invoices', $invoiceScope),
            default => null,
        };

        match ($sort) {
            'orders' => $query->orderByDesc('orders_count'),
            'invoices' => $query->orderByDesc('invoices_count'),
            'loyalty' => $query->orderByDesc('loyalty_points'),
            'last_purchase' => $query->orderByDesc('last_purchase_at'),
            'name' => $query->orderBy('name')->orderBy('phone'),
            default => $query->orderByDesc('total_spent'),
        };

        return $query->orderBy('id');
    }

    private function scopedCustomerInvoices(
        Builder $query,
        Request $request,
        ?string $from = null,
        ?string $to = null,
    ): Builder {
        $query = $this->forBranch($query, $request)
            ->where('status', '!=', InvoiceStatus::Void);

        if ($from !== null) {
            $query->whereDate('issued_at', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('issued_at', '<=', $to);
        }

        return $query;
    }

    private function scopedCustomerOrders(Builder $query, Request $request, string $from, string $to): Builder
    {
        return $this->forBranch($query, $request)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);
    }

    private function transformCustomerReportRow(Customer $customer): array
    {
        $invoicesCount = (int) ($customer->invoices_count ?? 0);
        $totalSpent = round((float) ($customer->total_spent ?? 0), 2);

        return [
            'id' => $customer->id,
            'name' => $customer->name ?? '—',
            'phone' => $customer->phone,
            'email' => $customer->email,
            'whatsapp' => $customer->whatsapp,
            'is_active' => (bool) $customer->is_active,
            'loyalty_points' => (int) $customer->loyalty_points,
            'invoices_count' => $invoicesCount,
            'orders_count' => (int) ($customer->orders_count ?? 0),
            'total_spent' => $totalSpent,
            'average_spent' => $invoicesCount > 0 ? round($totalSpent / $invoicesCount, 2) : 0,
            'last_purchase_at' => $customer->last_purchase_at,
            'registered_at' => $customer->created_at?->toIso8601String(),
        ];
    }

    /** @param  Collection<int, Customer>  $customers */
    private function buildCustomersSummary(Collection $customers): array
    {
        $rows = $customers->map(fn (Customer $customer) => $this->transformCustomerReportRow($customer));

        $customersCount = $rows->count();

        return [
            'customers_count' => $customersCount,
            'active_count' => $rows->where('is_active', true)->count(),
            'total_invoices' => (int) $rows->sum('invoices_count'),
            'total_orders' => (int) $rows->sum('orders_count'),
            'total_revenue' => round((float) $rows->sum('total_spent'), 2),
            'average_revenue' => $customersCount > 0
                ? round((float) $rows->sum('total_spent') / $customersCount, 2)
                : 0,
            'total_loyalty_points' => (int) $rows->sum('loyalty_points'),
        ];
    }

    /** @param  Collection<int, Customer>  $customers */
    private function topCustomersBreakdown(Collection $customers): array
    {
        return $customers
            ->map(fn (Customer $customer) => $this->transformCustomerReportRow($customer))
            ->sortByDesc('total_spent')
            ->take(10)
            ->values()
            ->all();
    }

    /** @return list<array{value: string, label: string}> */
    private function customerStatusOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'كل العملاء'],
            ['value' => 'active', 'label' => 'نشط فقط'],
            ['value' => 'inactive', 'label' => 'غير نشط'],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function customerActivityOptions(): array
    {
        return [
            ['value' => 'with_sales', 'label' => 'بمبيعات في الفترة'],
            ['value' => 'all', 'label' => 'كل المسجّلين'],
            ['value' => 'no_sales', 'label' => 'بدون مبيعات في الفترة'],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function customerSortOptions(): array
    {
        return [
            ['value' => 'revenue', 'label' => 'إجمالي المشتريات'],
            ['value' => 'invoices', 'label' => 'عدد الفواتير'],
            ['value' => 'orders', 'label' => 'عدد الطلبات'],
            ['value' => 'loyalty', 'label' => 'نقاط الولاء'],
            ['value' => 'last_purchase', 'label' => 'آخر شراء'],
            ['value' => 'name', 'label' => 'الاسم'],
        ];
    }
}
