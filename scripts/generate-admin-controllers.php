<?php

$basePath = dirname(__DIR__);

$crudResources = [
    ['ProductController', 'Product', 'products', 'ProductPolicy', true],
    ['ProductCategoryController', 'ProductCategory', 'product-categories', 'ProductCategoryPolicy', true],
    ['ModifierGroupController', 'ModifierGroup', 'modifier-groups', 'ModifierGroupPolicy', true],
    ['RecipeController', 'Recipe', 'recipes', 'RecipePolicy', true],
    ['WarehouseController', 'Warehouse', 'warehouses', 'WarehousePolicy', true],
    ['WastageController', 'WastageRecord', 'wastage', 'WastageRecordPolicy', true, 'wastage-records'],
    ['StockTransferController', 'StockTransfer', 'stock-transfers', 'StockTransferPolicy', true],
    ['StockCountController', 'StockCount', 'stock-counts', 'StockCountPolicy', true],
    ['SupplierController', 'Supplier', 'suppliers', 'SupplierPolicy', true],
    ['PurchaseOrderController', 'PurchaseOrder', 'purchase-orders', 'PurchaseOrderPolicy', true],
    ['PurchaseInvoiceController', 'PurchaseInvoice', 'purchase-invoices', 'PurchaseInvoicePolicy', true],
    ['OrderController', 'Order', 'orders', 'OrderPolicy', true],
    ['InvoiceController', 'Invoice', 'invoices', 'InvoicePolicy', true],
    ['RefundController', 'Refund', 'refunds', 'RefundPolicy', true],
    ['RestaurantTableController', 'RestaurantTable', 'restaurant-tables', 'RestaurantTablePolicy', true],
    ['TableSessionController', 'TableSession', 'table-sessions', 'TableSessionPolicy', true],
    ['CashRegisterController', 'CashRegister', 'cash-registers', 'CashRegisterPolicy', true],
    ['CashRegisterSessionController', 'CashRegisterSession', 'cash-register-sessions', 'CashRegisterSessionPolicy', true],
    ['ExpenseController', 'Expense', 'expenses', 'ExpensePolicy', true],
    ['ProductionOrderController', 'ProductionOrder', 'production-orders', 'ProductionOrderPolicy', true],
    ['ChartOfAccountController', 'ChartOfAccount', 'chart-of-accounts', 'ChartOfAccountPolicy', true],
    ['JournalEntryController', 'JournalEntry', 'journal-entries', 'JournalEntryPolicy', true],
    ['UserController', 'User', 'users', 'UserPolicy', true],
];

function routeParam(string $model): string
{
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $model));
}

function crudController(string $class, string $model, string $page, string $policy, bool $branchScoped, ?string $routeParam = null): string
{
    $routeParam = $routeParam ?? routeParam($model);
    $variable = lcfirst($model);
    $branchFilter = $branchScoped ? "\n            ->when(\$request->user()->branch_id, fn (\$q, \$branchId) => \$q->where('branch_id', \$branchId))" : '';

    return <<<PHP
<?php

namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Requests\\Admin\\{$model}\\Store{$model}Request;
use App\\Http\\Requests\\Admin\\{$model}\\Update{$model}Request;
use App\\Models\\{$model};
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Inertia\\Inertia;
use Inertia\\Response;

class {$class} extends AdminController
{
    public function index(Request \$request): Response
    {
        \$this->authorize('viewAny', {$model}::class);

        \${$variable}s = {$model}::query(){$branchFilter}
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/{$page}/index', [
            '{$routeParam}' => \${$variable}s,
        ]);
    }

    public function create(): Response
    {
        \$this->authorize('create', {$model}::class);

        return Inertia::render('admin/{$page}/create');
    }

    public function store(Store{$model}Request \$request): RedirectResponse
    {
        \$this->authorize('create', {$model}::class);

        {$model}::create(\$request->validated());

        return to_route('admin.{$routeParam}.index')
            ->with('toast', ['type' => 'success', 'message' => __('Created successfully.')]);
    }

    public function show({$model} \${$variable}): Response
    {
        \$this->authorize('view', \${$variable});

        return Inertia::render('admin/{$page}/show', [
            '{$routeParam}' => \${$variable},
        ]);
    }

    public function edit({$model} \${$variable}): Response
    {
        \$this->authorize('update', \${$variable});

        return Inertia::render('admin/{$page}/edit', [
            '{$routeParam}' => \${$variable},
        ]);
    }

    public function update(Update{$model}Request \$request, {$model} \${$variable}): RedirectResponse
    {
        \$this->authorize('update', \${$variable});

        \${$variable}->update(\$request->validated());

        return to_route('admin.{$routeParam}.index')
            ->with('toast', ['type' => 'success', 'message' => __('Updated successfully.')]);
    }

    public function destroy({$model} \${$variable}): RedirectResponse
    {
        \$this->authorize('delete', \${$variable});

        \${$variable}->delete();

        return to_route('admin.{$routeParam}.index')
            ->with('toast', ['type' => 'success', 'message' => __('Deleted successfully.')]);
    }
}

PHP;
}

foreach ($crudResources as $item) {
    [$class, $model, $page, $policy, $branchScoped] = $item;
    $routeParam = $item[5] ?? routeParam($model);
    $content = crudController($class, $model, $page, $policy, $branchScoped, $routeParam);
    file_put_contents("{$basePath}/app/Http/Controllers/Admin/{$class}.php", $content);
}

// DashboardController
file_put_contents("{$basePath}/app/Http/Controllers/Admin/DashboardController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('view', 'dashboard');

        $today = Carbon::today();
        $branchId = $request->user()->branch_id;

        $orderQuery = Order::query()->whereDate('created_at', $today);
        $invoiceQuery = Invoice::query()->whereDate('created_at', $today);
        $expenseQuery = Expense::query()->whereDate('expense_date', $today);
        $stockQuery = StockMovement::query()->whereDate('created_at', $today);

        if ($branchId) {
            $orderQuery->where('branch_id', $branchId);
            $invoiceQuery->where('branch_id', $branchId);
            $expenseQuery->where('branch_id', $branchId);
            $stockQuery->where('branch_id', $branchId);
        }

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'orders_count' => (clone $orderQuery)->count(),
                'orders_total' => (float) (clone $orderQuery)->sum('total'),
                'invoices_count' => (clone $invoiceQuery)->count(),
                'invoices_total' => (float) (clone $invoiceQuery)->sum('total'),
                'expenses_total' => (float) (clone $expenseQuery)->sum('amount'),
                'stock_movements_count' => (clone $stockQuery)->count(),
            ],
        ]);
    }
}

PHP);

// StockController
file_put_contents("{$basePath}/app/Http/Controllers/Admin/StockController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Stock\AdjustStockRequest;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends AdminController
{
    public function __construct(
        protected StockService $stockService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StockMovement::class);

        $movements = StockMovement::query()
            ->with(['warehouse', 'product', 'user'])
            ->when($request->user()->branch_id, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/inventory/movements', [
            'movements' => $movements,
        ]);
    }

    public function adjust(AdjustStockRequest $request): RedirectResponse
    {
        $this->authorize('adjust', StockMovement::class);

        $this->stockService->recordMovement([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'branch_id' => $request->user()->branch_id,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => __('Stock adjusted successfully.')]);
    }
}

PHP);

// AccountingReportController
file_put_contents("{$basePath}/app/Http/Controllers/Admin/AccountingReportController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin;

use App\Admin\AccountingReport;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountingReportController extends AdminController
{
    public function profitLoss(Request $request): Response
    {
        $this->authorize('profitLoss', AccountingReport::class);

        $branchId = $request->user()->branch_id;
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        $lines = JournalEntryLine::query()
            ->with('account')
            ->when($branchId, fn ($q) => $q->whereHas('journalEntry', fn ($jq) => $jq->where('branch_id', $branchId)))
            ->when($from, fn ($q) => $q->whereHas('journalEntry', fn ($jq) => $jq->whereDate('date', '>=', $from)))
            ->when($to, fn ($q) => $q->whereHas('journalEntry', fn ($jq) => $jq->whereDate('date', '<=', $to)))
            ->get();

        return Inertia::render('admin/accounting/reports/profit-loss', [
            'lines' => $lines,
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        $this->authorize('balanceSheet', AccountingReport::class);

        $branchId = $request->user()->branch_id;
        $asOf = $request->date('as_of')?->toDateString();

        $accounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return Inertia::render('admin/accounting/reports/balance-sheet', [
            'accounts' => $accounts,
            'filters' => $request->only(['as_of']),
            'asOf' => $asOf,
        ]);
    }

    public function trialBalance(Request $request): Response
    {
        $this->authorize('trialBalance', AccountingReport::class);

        $branchId = $request->user()->branch_id;
        $asOf = $request->date('as_of')?->toDateString();

        $lines = JournalEntryLine::query()
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->with('account')
            ->when($branchId, fn ($q) => $q->whereHas('journalEntry', fn ($jq) => $jq->where('branch_id', $branchId)))
            ->when($asOf, fn ($q) => $q->whereHas('journalEntry', fn ($jq) => $jq->whereDate('date', '<=', $asOf)))
            ->groupBy('account_id')
            ->get();

        return Inertia::render('admin/accounting/reports/trial-balance', [
            'lines' => $lines,
            'filters' => $request->only(['as_of']),
        ]);
    }
}

PHP);

// ReportController
file_put_contents("{$basePath}/app/Http/Controllers/Admin/ReportController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Report;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends AdminController
{
    public function sales(Request $request): Response
    {
        $this->authorize('sales', 'report');

        $branchId = $request->user()->branch_id;

        $invoices = Invoice::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->date('from'), fn ($q, $from) => $q->whereDate('issued_at', '>=', $from))
            ->when($request->date('to'), fn ($q, $to) => $q->whereDate('issued_at', '<=', $to))
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/reports/sales', [
            'invoices' => $invoices,
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function products(Request $request): Response
    {
        $this->authorize('products', 'report');

        $branchId = $request->user()->branch_id;

        $items = InvoiceItem::query()
            ->selectRaw('product_id, product_name, SUM(quantity) as total_qty, SUM(total) as total_revenue')
            ->when($branchId, fn ($q) => $q->whereHas('invoice', fn ($iq) => $iq->where('branch_id', $branchId)))
            ->when($request->date('from'), fn ($q, $from) => $q->whereHas('invoice', fn ($iq) => $iq->whereDate('issued_at', '>=', $from)))
            ->when($request->date('to'), fn ($q, $to) => $q->whereHas('invoice', fn ($iq) => $iq->whereDate('issued_at', '<=', $to)))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_revenue')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/reports/products', [
            'items' => $items,
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function inventory(Request $request): Response
    {
        $this->authorize('inventory', 'report');

        $branchId = $request->user()->branch_id;

        $balances = StockBalance::query()
            ->with(['product', 'warehouse'])
            ->when($branchId, fn ($q) => $q->whereHas('warehouse', fn ($wq) => $wq->where('branch_id', $branchId)))
            ->orderBy('product_id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/reports/inventory', [
            'balances' => $balances,
        ]);
    }

    public function customers(Request $request): Response
    {
        $this->authorize('customers', 'report');

        $customers = Customer::query()
            ->withCount('orders')
            ->when($request->input('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/reports/customers', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
        ]);
    }
}

PHP);

// RoleController
file_put_contents("{$basePath}/app/Http/Controllers/Admin/RoleController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Role\StoreRoleRequest;
use App\Http\Requests\Admin\Role\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->with('permissions')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/roles/index', [
            'roles' => $roles,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('admin/roles/create', [
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = Role::create(['name' => $request->validated('name'), 'guard_name' => 'web']);
        $role->syncPermissions($request->validated('permissions', []));

        return to_route('admin.roles.index')
            ->with('toast', ['type' => 'success', 'message' => __('Role created successfully.')]);
    }

    public function show(Role $role): Response
    {
        $this->authorize('view', $role);

        $role->load('permissions');

        return Inertia::render('admin/roles/show', [
            'role' => $role,
        ]);
    }

    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        $role->load('permissions');

        return Inertia::render('admin/roles/edit', [
            'role' => $role,
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        return to_route('admin.roles.index')
            ->with('toast', ['type' => 'success', 'message' => __('Role updated successfully.')]);
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $role->delete();

        return to_route('admin.roles.index')
            ->with('toast', ['type' => 'success', 'message' => __('Role deleted successfully.')]);
    }
}

PHP);

// AuditLogController
file_put_contents("{$basePath}/app/Http/Controllers/Admin/AuditLogController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->user()->branch_id, fn ($q, $branchId) => $q->where('branch_id', $branchId))
            ->when($request->input('action'), fn ($q, $action) => $q->where('action', $action))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/audit-logs/index', [
            'logs' => $logs,
            'filters' => $request->only(['action']),
        ]);
    }

    public function show(AuditLog $auditLog): Response
    {
        $this->authorize('view', $auditLog);

        $auditLog->load('user');

        return Inertia::render('admin/audit-logs/show', [
            'auditLog' => $auditLog,
        ]);
    }
}

PHP);

// SettingsController
file_put_contents("{$basePath}/app/Http/Controllers/Admin/SettingsController.php", <<<'PHP'
<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Settings;
use App\Http\Requests\Admin\Settings\UpdateBusinessSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('view', 'settings');

        return Inertia::render('admin/settings/business', [
            'settings' => Cache::get('business_settings', []),
        ]);
    }

    public function update(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        $this->authorize('update', 'settings');

        Cache::forever('business_settings', $request->validated());

        return back()->with('toast', ['type' => 'success', 'message' => __('Settings updated successfully.')]);
    }
}

PHP);

echo "Controllers created.\n";
