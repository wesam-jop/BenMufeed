<?php

use App\Http\Controllers\Admin\System\BranchSelectController;
use App\Http\Controllers\Admin\Accounting\AccountingReportController;
use App\Http\Controllers\Admin\System\AuditLogController;
use App\Http\Controllers\Admin\Accounting\CashRegisterController;
use App\Http\Controllers\Admin\Accounting\CashRegisterSessionController;
use App\Http\Controllers\Admin\Accounting\ChartOfAccountController;
use App\Http\Controllers\Admin\System\DashboardController;
use App\Http\Controllers\Admin\Accounting\ExpenseController;
use App\Http\Controllers\Admin\Inventory\InventoryMaterialController;
use App\Http\Controllers\Admin\Sales\InvoiceController;
use App\Http\Controllers\Admin\Accounting\JournalEntryController;
use App\Http\Controllers\Admin\Catalog\ModifierGroupController;
use App\Http\Controllers\Admin\Sales\OrderController;
use App\Http\Controllers\Admin\Catalog\ProductCategoryController;
use App\Http\Controllers\Admin\Catalog\ProductController;
use App\Http\Controllers\Admin\Inventory\PurchaseInvoiceController;
use App\Http\Controllers\Admin\Catalog\RecipeController;
use App\Http\Controllers\Admin\Sales\RefundController;
use App\Http\Controllers\Admin\System\ReportController;
use App\Http\Controllers\Admin\System\ReceiptDesignerController;
use App\Http\Controllers\Admin\Sales\RestaurantTableController;
use App\Http\Controllers\Admin\System\RoleController;
use App\Http\Controllers\Admin\Cms\CmsPageController;
use App\Http\Controllers\Admin\System\CurrencyController;
use App\Http\Controllers\Admin\System\SettingsController;
use App\Http\Controllers\Admin\Cms\SiteSettingsController;
use App\Http\Controllers\Admin\Inventory\StockController;
use App\Http\Controllers\Admin\Inventory\StockCountController;
use App\Http\Controllers\Admin\Inventory\StockTransferController;
use App\Http\Controllers\Admin\Inventory\SupplierController;
use App\Http\Controllers\Admin\Sales\TableSessionController;
use App\Http\Controllers\Admin\System\UserController;
use App\Http\Controllers\Admin\Inventory\WarehouseController;
use App\Http\Controllers\Admin\Inventory\WastageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['staff', 'auth:web', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/branch/select', [BranchSelectController::class, 'index'])->name('branch.select');
        Route::post('/branch/select', [BranchSelectController::class, 'store'])->name('branch.store');
    });

Route::middleware(['staff', 'auth:web', 'verified', 'branch'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('permission:dashboard.view')
            ->name('dashboard');

        Route::resource('products', ProductController::class)
            ->middleware('permission:products.view|products.create|products.update|products.delete');

        Route::resource('product-categories', ProductCategoryController::class)
            ->middleware('permission:product-categories.view|product-categories.create|product-categories.update|product-categories.delete');

        Route::resource('modifier-groups', ModifierGroupController::class)
            ->middleware('permission:modifier-groups.view|modifier-groups.create|modifier-groups.update|modifier-groups.delete');

        Route::resource('recipes', RecipeController::class)
            ->only(['index'])
            ->middleware('permission:recipes.view');

        Route::resource('warehouses', WarehouseController::class)
            ->middleware('permission:warehouses.view|warehouses.create|warehouses.update|warehouses.delete');

        Route::get('inventory/movements', [StockController::class, 'index'])
            ->middleware('permission:inventory.view')
            ->name('inventory.movements');

        Route::redirect('inventory/stock', '/admin/inventory/materials')->name('inventory.stock');

        Route::prefix('inventory/materials')->name('inventory.materials.')->group(function () {
            Route::get('/', [InventoryMaterialController::class, 'index'])
                ->middleware('permission:inventory.view|products.view')
                ->name('index');

            Route::get('create', [InventoryMaterialController::class, 'create'])
                ->middleware('permission:products.create')
                ->name('create');

            Route::post('/', [InventoryMaterialController::class, 'store'])
                ->middleware('permission:products.create')
                ->name('store');

            Route::get('{product}/edit', [InventoryMaterialController::class, 'edit'])
                ->middleware('permission:products.update')
                ->name('edit');

            Route::put('{product}', [InventoryMaterialController::class, 'update'])
                ->middleware('permission:products.update')
                ->name('update');
        });

        Route::redirect('delivery', '/admin/orders')->name('delivery.index');

        Route::get('restaurant-tables/floor-plan', fn () => Inertia::render('admin/restaurant-tables/floor-plan'))
            ->middleware('permission:restaurant-tables.view')
            ->name('restaurant-tables.floor-plan');

        Route::post('restaurant-tables/regenerate-shared-code', [RestaurantTableController::class, 'regenerateSharedCode'])
            ->middleware('permission:restaurant-tables.update')
            ->name('restaurant-tables.regenerate-shared-code');

        Route::post('restaurant-tables/qr-logo', [RestaurantTableController::class, 'uploadQrLogo'])
            ->middleware('permission:restaurant-tables.update')
            ->name('restaurant-tables.qr-logo');

        Route::delete('restaurant-tables/qr-logo', [RestaurantTableController::class, 'removeQrLogo'])
            ->middleware('permission:restaurant-tables.update')
            ->name('restaurant-tables.qr-logo.destroy');

        Route::post('restaurant-tables/{restaurant_table}/regenerate-code', [RestaurantTableController::class, 'regenerateCode'])
            ->middleware('permission:restaurant-tables.update')
            ->name('restaurant-tables.regenerate-code');

        Route::post('inventory/adjust', [StockController::class, 'adjust'])
            ->middleware('permission:inventory.adjust')
            ->name('inventory.adjust');

        Route::resource('wastage-records', WastageController::class)
            ->middleware('permission:wastage.view|wastage.create|wastage.update|wastage.delete');

        Route::resource('stock-transfers', StockTransferController::class)
            ->middleware('permission:stock-transfers.view|stock-transfers.create|stock-transfers.update|stock-transfers.delete');

        Route::post('stock-transfers/{stock_transfer}/execute', [StockTransferController::class, 'execute'])
            ->middleware('permission:stock-transfers.update')
            ->name('stock-transfers.execute');

        Route::resource('stock-counts', StockCountController::class)
            ->middleware('permission:stock-counts.view|stock-counts.create|stock-counts.update|stock-counts.delete');

        Route::post('stock-counts/{stock_count}/approve', [StockCountController::class, 'approve'])
            ->middleware('permission:stock-counts.update')
            ->name('stock-counts.approve');

        Route::resource('suppliers', SupplierController::class)
            ->middleware('permission:suppliers.view|suppliers.create|suppliers.update|suppliers.delete');

        Route::resource('purchase-invoices', PurchaseInvoiceController::class)
            ->middleware('permission:purchase-invoices.view|purchase-invoices.create|purchase-invoices.update|purchase-invoices.delete');

        Route::post('purchase-invoices/{purchase_invoice}/receive', [PurchaseInvoiceController::class, 'receive'])
            ->middleware('permission:purchase-invoices.update')
            ->name('purchase-invoices.receive');

        Route::resource('orders', OrderController::class)
            ->middleware('permission:orders.view|orders.create|orders.update|orders.delete');

        Route::resource('invoices', InvoiceController::class)
            ->middleware('permission:invoices.view|invoices.create|invoices.update|invoices.delete');

        Route::resource('refunds', RefundController::class)
            ->middleware('permission:refunds.view|refunds.create|refunds.update|refunds.delete');

        Route::resource('restaurant-tables', RestaurantTableController::class)
            ->middleware('permission:restaurant-tables.view|restaurant-tables.create|restaurant-tables.update|restaurant-tables.delete');

        Route::resource('table-sessions', TableSessionController::class)
            ->middleware('permission:table-sessions.view|table-sessions.create|table-sessions.update|table-sessions.delete');

        Route::resource('cash-registers', CashRegisterController::class)
            ->middleware('permission:cash-registers.view|cash-registers.create|cash-registers.update|cash-registers.delete');

        Route::resource('cash-register-sessions', CashRegisterSessionController::class)
            ->middleware('permission:cash-register-sessions.view|cash-register-sessions.create|cash-register-sessions.update|cash-register-sessions.delete');

        Route::post('cash-register-sessions/{cash_register_session}/close', [CashRegisterSessionController::class, 'close'])
            ->middleware('permission:cash-register-sessions.update')
            ->name('cash-register-sessions.close');

        Route::resource('expenses', ExpenseController::class)
            ->middleware('permission:expenses.view|expenses.create|expenses.update|expenses.delete');

        Route::resource('chart-of-accounts', ChartOfAccountController::class)
            ->middleware('permission:chart-of-accounts.view|chart-of-accounts.create|chart-of-accounts.update|chart-of-accounts.delete');

        Route::resource('journal-entries', JournalEntryController::class)
            ->middleware('permission:journal-entries.view|journal-entries.create|journal-entries.update|journal-entries.delete');

        Route::prefix('accounting/reports')->name('accounting.reports.')->group(function () {
            Route::get('profit-loss', [AccountingReportController::class, 'profitLoss'])
                ->middleware('permission:accounting-reports.view')
                ->name('profit-loss');

            Route::get('balance-sheet', [AccountingReportController::class, 'balanceSheet'])
                ->middleware('permission:accounting-reports.view')
                ->name('balance-sheet');

            Route::get('trial-balance', [AccountingReportController::class, 'trialBalance'])
                ->middleware('permission:accounting-reports.view')
                ->name('trial-balance');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('sales', [ReportController::class, 'sales'])
                ->middleware('permission:reports.view')
                ->name('sales');

            Route::get('products', [ReportController::class, 'products'])
                ->middleware('permission:reports.view')
                ->name('products');

            Route::get('inventory', [ReportController::class, 'inventory'])
                ->middleware('permission:reports.view')
                ->name('inventory');

            Route::get('customers', [ReportController::class, 'customers'])
                ->middleware('permission:reports.view')
                ->name('customers');
        });

        Route::resource('users', UserController::class)
            ->middleware('permission:users.view|users.create|users.update|users.delete');

        Route::resource('roles', RoleController::class)
            ->middleware('permission:roles.view|roles.create|roles.update|roles.delete');

        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->middleware('permission:audit.view')
            ->name('audit-logs.index');

        Route::get('audit-logs/{audit_log}', [AuditLogController::class, 'show'])
            ->middleware('permission:audit.view')
            ->name('audit-logs.show');

        Route::get('settings/business', [SettingsController::class, 'index'])
            ->middleware('permission:settings.view')
            ->name('settings.business');

        Route::put('settings/business', [SettingsController::class, 'update'])
            ->middleware('permission:settings.update')
            ->name('settings.business.update');

        Route::get('settings/receipt-designer', [ReceiptDesignerController::class, 'edit'])
            ->middleware('permission:settings.view')
            ->name('settings.receipt-designer');

        Route::put('settings/receipt-designer', [ReceiptDesignerController::class, 'update'])
            ->middleware('permission:settings.update')
            ->name('settings.receipt-designer.update');

        Route::post('settings/receipt-designer/reset', [ReceiptDesignerController::class, 'reset'])
            ->middleware('permission:settings.update')
            ->name('settings.receipt-designer.reset');

        Route::get('settings/currencies', [CurrencyController::class, 'index'])
            ->middleware('permission:settings.view')
            ->name('settings.currencies');
        Route::post('settings/currencies', [CurrencyController::class, 'store'])
            ->middleware('permission:settings.update')
            ->name('settings.currencies.store');
        Route::put('settings/currencies/{currency}', [CurrencyController::class, 'update'])
            ->middleware('permission:settings.update')
            ->name('settings.currencies.update');
        Route::post('settings/currencies/{currency}/make-primary', [CurrencyController::class, 'makePrimary'])
            ->middleware('permission:settings.update')
            ->name('settings.currencies.make-primary');
        Route::delete('settings/currencies/{currency}', [CurrencyController::class, 'destroy'])
            ->middleware('permission:settings.update')
            ->name('settings.currencies.destroy');

        Route::get('site', [SiteSettingsController::class, 'index'])
            ->middleware('permission:settings.view')
            ->name('site.index');

        Route::get('pages', [CmsPageController::class, 'index'])
            ->middleware('permission:settings.view')
            ->name('pages.index');
        Route::get('pages/create', [CmsPageController::class, 'create'])
            ->middleware('permission:settings.update')
            ->name('pages.create');
        Route::post('pages', [CmsPageController::class, 'store'])
            ->middleware('permission:settings.update')
            ->name('pages.store');
        Route::get('pages/{page}/builder', [CmsPageController::class, 'builder'])
            ->middleware('permission:settings.view')
            ->name('pages.builder');
        Route::put('pages/{page}/builder', [CmsPageController::class, 'updateBuilder'])
            ->middleware('permission:settings.update')
            ->name('pages.builder.update');
        Route::post('pages/{page}/toggle-publish', [CmsPageController::class, 'togglePublish'])
            ->middleware('permission:settings.update')
            ->name('pages.toggle-publish');
        Route::delete('pages/{page}', [CmsPageController::class, 'destroy'])
            ->middleware('permission:settings.update')
            ->name('pages.destroy');

        Route::post('site', [SiteSettingsController::class, 'update'])
            ->middleware('permission:settings.update')
            ->name('site.update');

        Route::get('site/messages', [SiteSettingsController::class, 'messages'])
            ->middleware('permission:settings.view')
            ->name('site.messages');

        Route::get('site/messages/{message}', [SiteSettingsController::class, 'showMessage'])
            ->middleware('permission:settings.view')
            ->name('site.messages.show');

        Route::patch('site/messages/read-all', [SiteSettingsController::class, 'markAllMessagesRead'])
            ->middleware('permission:settings.update')
            ->name('site.messages.read-all');

        Route::patch('site/messages/{message}/read', [SiteSettingsController::class, 'markMessageRead'])
            ->middleware('permission:settings.update')
            ->name('site.messages.read');

        Route::patch('site/messages/{message}/unread', [SiteSettingsController::class, 'markMessageUnread'])
            ->middleware('permission:settings.update')
            ->name('site.messages.unread');

        Route::delete('site/messages/{message}', [SiteSettingsController::class, 'destroyMessage'])
            ->middleware('permission:settings.update')
            ->name('site.messages.destroy');
    });
