<?php

namespace App\Services;

use App\Enums\EmployeeLedgerEntryType;
use App\Enums\FinancialMovementType;
use App\Enums\InvoiceStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use App\Models\Employee;
use App\Models\EmployeeLedgerEntry;
use App\Models\FinancialMovement;
use App\Models\Material;
use App\Models\Order;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Support\RestaurantMetricsCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ProfitsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?string $from = null, ?string $to = null): array
    {
        $cacheKey = RestaurantMetricsCache::profitsSummaryKey($from, $to);

        return Cache::remember($cacheKey, 120, fn (): array => $this->buildSummary($from, $to));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSummary(?string $from = null, ?string $to = null): array
    {
        $fromDate = filled($from) ? Carbon::parse($from)->startOfDay() : null;
        $toDate = filled($to) ? Carbon::parse($to)->endOfDay() : null;

        $posSales = $this->sumOrders($fromDate, $toDate);
        $invoiceSales = $this->sumSalesInvoices($fromDate, $toDate);
        $totalSales = round($posSales + $invoiceSales, 2);

        $purchases = $this->sumPurchaseInvoices($fromDate, $toDate);
        $expenses = $this->sumGeneralExpenses($fromDate, $toDate);
        $salariesPaid = $this->sumLedgerEntries(EmployeeLedgerEntryType::SalaryPayment, $fromDate, $toDate);
        $salaryAdvances = $this->sumLedgerEntries(EmployeeLedgerEntryType::Advance, $fromDate, $toDate);
        $salariesAccrued = $this->sumLedgerEntries(EmployeeLedgerEntryType::SalaryAccrual, $fromDate, $toDate);
        $totalPayrollOut = round($salariesPaid + $salaryAdvances, 2);

        $externalReceipts = $this->sumFinancialMovements(FinancialMovementType::ExternalReceipt, $fromDate, $toDate);
        $customerPayments = $this->sumFinancialMovements(FinancialMovementType::CustomerPayment, $fromDate, $toDate);
        $ownerWithdrawals = $this->sumFinancialMovements(FinancialMovementType::OwnerWithdrawal, $fromDate, $toDate);

        $debtsPayable = $this->totalDebtsPayable();
        $debtsReceivable = $this->totalDebtsReceivable();
        $inventoryValue = Material::totalActiveStockValue();
        $payrollLiability = $this->totalPayrollLiability();

        $totalIncome = round($totalSales + $externalReceipts, 2);
        $totalCosts = round($purchases + $expenses + $totalPayrollOut, 2);
        $operatingProfit = round($totalIncome - $totalCosts, 2);
        $netAfterWithdrawals = round($operatingProfit - $ownerWithdrawals, 2);
        $grossProfit = round($totalSales - $purchases, 2);

        $margin = $totalSales > 0 ? round(($operatingProfit / $totalSales) * 100, 1) : null;

        return [
            'sales' => [
                'pos' => $posSales,
                'invoices' => $invoiceSales,
                'total' => $totalSales,
            ],
            'purchases' => $purchases,
            'expenses' => $expenses,
            'payroll' => [
                'paid' => $salariesPaid,
                'advances' => $salaryAdvances,
                'accrued' => $salariesAccrued,
                'total_out' => $totalPayrollOut,
                'outstanding_liability' => $payrollLiability,
            ],
            'other_income' => [
                'external_receipts' => $externalReceipts,
            ],
            'other_outgoing' => [
                'customer_payments' => $customerPayments,
                'owner_withdrawals' => $ownerWithdrawals,
            ],
            'debts' => [
                'payable' => $debtsPayable,
                'receivable' => $debtsReceivable,
            ],
            'inventory' => [
                'total_value' => $inventoryValue,
            ],
            'profit' => [
                'gross' => $grossProfit,
                'operating' => $operatingProfit,
                'net' => $operatingProfit,
                'owner_withdrawals' => $ownerWithdrawals,
                'net_after_withdrawals' => $netAfterWithdrawals,
                'margin_percent' => $margin,
                'total_income' => $totalIncome,
                'total_costs' => $totalCosts,
            ],
        ];
    }

    protected function sumOrders(?Carbon $from, ?Carbon $to): float
    {
        $query = Order::query()
            ->where('source', OrderSource::Pos)
            ->where('status', OrderStatus::Completed);

        $this->applyClosedDateRange($query, $from, $to);

        return round((float) $query->sum('total'), 2);
    }

    protected function sumSalesInvoices(?Carbon $from, ?Carbon $to): float
    {
        $query = SalesInvoice::query()
            ->where('status', InvoiceStatus::Confirmed);

        $this->applyDateRange($query, 'invoice_date', $from, $to);

        return round((float) $query->sum('total'), 2);
    }

    protected function sumPurchaseInvoices(?Carbon $from, ?Carbon $to): float
    {
        $query = PurchaseInvoice::query()
            ->where('status', InvoiceStatus::Confirmed);

        $this->applyDateRange($query, 'invoice_date', $from, $to);

        return round((float) $query->sum('total'), 2);
    }

    protected function sumGeneralExpenses(?Carbon $from, ?Carbon $to): float
    {
        $query = FinancialMovement::query()
            ->where('type', FinancialMovementType::Expense)
            ->whereNull('employee_id');

        $this->applyDateRange($query, 'movement_date', $from, $to);

        return round((float) $query->sum('amount'), 2);
    }

    protected function sumFinancialMovements(FinancialMovementType $type, ?Carbon $from, ?Carbon $to): float
    {
        $query = FinancialMovement::query()->where('type', $type);

        $this->applyDateRange($query, 'movement_date', $from, $to);

        return round((float) $query->sum('amount'), 2);
    }

    protected function sumLedgerEntries(EmployeeLedgerEntryType $type, ?Carbon $from, ?Carbon $to): float
    {
        $query = EmployeeLedgerEntry::query()->where('type', $type);

        $this->applyDateRange($query, 'entry_date', $from, $to);

        return round((float) $query->sum('amount'), 2);
    }

    protected function totalDebtsPayable(): float
    {
        return round((float) PurchaseInvoice::query()
            ->where('status', InvoiceStatus::Confirmed)
            ->where('payment_type', PaymentType::Credit)
            ->selectRaw('COALESCE(SUM(GREATEST(total - paid_amount, 0)), 0) as remaining')
            ->value('remaining'), 2);
    }

    protected function totalDebtsReceivable(): float
    {
        return round((float) SalesInvoice::query()
            ->where('status', InvoiceStatus::Confirmed)
            ->where('payment_type', PaymentType::Credit)
            ->selectRaw('COALESCE(SUM(GREATEST(total - paid_amount, 0)), 0) as remaining')
            ->value('remaining'), 2);
    }

    protected function totalPayrollLiability(): float
    {
        return round(
            Employee::query()
                ->where('is_active', true)
                ->get()
                ->sum(fn (Employee $employee) => max(0, $employee->balance())),
            2,
        );
    }

    protected function applyDateRange($query, string $column, ?Carbon $from, ?Carbon $to): void
    {
        if ($from !== null) {
            $query->whereDate($column, '>=', $from->toDateString());
        }

        if ($to !== null) {
            $query->whereDate($column, '<=', $to->toDateString());
        }
    }

    protected function applyClosedDateRange($query, ?Carbon $from, ?Carbon $to): void
    {
        $query->where(function ($inner) use ($from, $to): void {
            $inner->where(function ($closed) use ($from, $to): void {
                $closed->whereNotNull('closed_at');
                $this->applyDateRange($closed, 'closed_at', $from, $to);
            })->orWhere(function ($legacy) use ($from, $to): void {
                $legacy->whereNull('closed_at');
                $this->applyDateRange($legacy, 'created_at', $from, $to);
            });
        });
    }
}
