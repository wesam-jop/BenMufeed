<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\System\AdminController;

use App\Admin\Dashboard;
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
        $this->authorize('view', Dashboard::class);

        $today = Carbon::today();
        $branchId = $this->branchId($request);

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
