<?php

namespace App\Support;

use App\Models\PrintJob;
use App\Services\PrintJobService;

trait DispatchesPrintJobs
{
    protected function dispatchKitchenPrint(int $ticketId, string $source = 'auto'): void
    {
        $job = app(PrintJobService::class)->queueKitchen($ticketId, $source);

        $this->dispatch(
            'print-kitchen-ticket',
            ticketId: $ticketId,
            printJobId: $job->getKey(),
        );
    }

    protected function dispatchCashierPrint(int $orderId, string $source = 'auto'): void
    {
        $job = app(PrintJobService::class)->queueCashier($orderId, $source);

        $this->dispatch(
            'print-cashier-receipt',
            orderId: $orderId,
            printJobId: $job->getKey(),
        );
    }

    protected function dispatchPrintJob(PrintJob $job): void
    {
        match ($job->kind) {
            \App\Enums\PrintJobKind::KitchenTicket => $this->dispatch(
                'print-kitchen-ticket',
                ticketId: (int) $job->kitchen_ticket_id,
                printJobId: $job->getKey(),
            ),
            \App\Enums\PrintJobKind::CashierReceipt => $this->dispatch(
                'print-cashier-receipt',
                orderId: (int) $job->order_id,
                printJobId: $job->getKey(),
            ),
        };
    }
}
