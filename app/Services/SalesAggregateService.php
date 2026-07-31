<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PosServiceType;
use App\Models\DailySalesSummary;
use App\Models\Order;
use App\Models\SalesInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesAggregateService
{
    /**
     * @return array<string, int|float>
     */
    public function buildSummary(string $date): array
    {
        $from = Carbon::parse($date)->startOfDay();
        $to = Carbon::parse($date)->endOfDay();

        $posBase = Order::query()
            ->where('source', OrderSource::Pos)
            ->where('status', OrderStatus::Completed);

        $this->applyClosedDateRange($posBase, $from, $to);

        $posOrdersCount = (clone $posBase)->count();
        $posTotal = round((float) (clone $posBase)->sum('total'), 2);
        $collectedTotal = round((float) (clone $posBase)->sum(DB::raw('COALESCE(paid_amount, total)')), 2);

        $takeawayBase = (clone $posBase)->where('service_type', PosServiceType::Takeaway);
        $tableBase = (clone $posBase)->where('service_type', PosServiceType::Table);

        $takeawayCount = (clone $takeawayBase)->count();
        $tableCount = (clone $tableBase)->count();
        $takeawayTotal = round((float) (clone $takeawayBase)->sum('total'), 2);
        $tableTotal = round((float) (clone $tableBase)->sum('total'), 2);

        $invoiceSales = SalesInvoice::query()
            ->where('status', InvoiceStatus::Confirmed)
            ->whereDate('invoice_date', '>=', $from->toDateString())
            ->whereDate('invoice_date', '<=', $to->toDateString());

        $invoiceCount = (clone $invoiceSales)->count();
        $invoiceTotal = round((float) (clone $invoiceSales)->sum('total'), 2);

        $openTables = Order::query()
            ->where('source', OrderSource::Pos)
            ->where('service_type', PosServiceType::Table)
            ->where('status', OrderStatus::Open);

        $openTablesCount = (clone $openTables)->count();
        $openTablesTotal = round((float) (clone $openTables)->sum('total'), 2);

        return [
            'orders_count' => $posOrdersCount,
            'takeaway_count' => $takeawayCount,
            'table_count' => $tableCount,
            'takeaway_total' => $takeawayTotal,
            'table_total' => $tableTotal,
            'pos_total' => $posTotal,
            'collected_total' => $collectedTotal,
            'invoice_count' => $invoiceCount,
            'invoice_total' => $invoiceTotal,
            'grand_total' => round($posTotal + $invoiceTotal, 2),
            'open_tables_count' => $openTablesCount,
            'open_tables_total' => $openTablesTotal,
        ];
    }

    public function persistDailySummary(string $date): DailySalesSummary
    {
        $summary = $this->buildSummary($date);
        $timestamp = now();

        DailySalesSummary::query()->upsert(
            [
                'sale_date' => $date,
                'pos_orders_count' => $summary['orders_count'],
                'takeaway_count' => $summary['takeaway_count'],
                'table_count' => $summary['table_count'],
                'takeaway_total' => $summary['takeaway_total'],
                'table_total' => $summary['table_total'],
                'pos_total' => $summary['pos_total'],
                'collected_total' => $summary['collected_total'],
                'invoice_count' => $summary['invoice_count'],
                'invoice_total' => $summary['invoice_total'],
                'grand_total' => $summary['grand_total'],
                'open_tables_count' => $summary['open_tables_count'],
                'open_tables_total' => $summary['open_tables_total'],
                'computed_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            ['sale_date'],
            [
                'pos_orders_count',
                'takeaway_count',
                'table_count',
                'takeaway_total',
                'table_total',
                'pos_total',
                'collected_total',
                'invoice_count',
                'invoice_total',
                'grand_total',
                'open_tables_count',
                'open_tables_total',
                'computed_at',
                'updated_at',
            ],
        );

        return DailySalesSummary::query()
            ->whereDate('sale_date', $date)
            ->firstOrFail();
    }

    protected function applyClosedDateRange($query, Carbon $from, Carbon $to): void
    {
        $query->where(function ($inner) use ($from, $to): void {
            $inner->where(function ($closed) use ($from, $to): void {
                $closed->whereNotNull('closed_at')
                    ->whereDate('closed_at', '>=', $from->toDateString())
                    ->whereDate('closed_at', '<=', $to->toDateString());
            })->orWhere(function ($legacy) use ($from, $to): void {
                $legacy->whereNull('closed_at')
                    ->whereDate('created_at', '>=', $from->toDateString())
                    ->whereDate('created_at', '<=', $to->toDateString());
            });
        });
    }
}
