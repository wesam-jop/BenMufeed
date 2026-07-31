<?php

namespace App\Services;

use App\Enums\PrintJobKind;
use App\Enums\PrintJobStatus;
use App\Models\KitchenTicket;
use App\Models\Order;
use App\Models\PosSetting;
use App\Models\PrintJob;
use App\Jobs\SendPrintFailureNotifications;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PrintJobService
{
    public function __construct(
        protected KitchenService $kitchen,
    ) {}

    public function queueKitchen(int $ticketId, string $source = 'auto'): PrintJob
    {
        $settings = PosSetting::current();

        return PrintJob::query()->create([
            'kind' => PrintJobKind::KitchenTicket,
            'status' => PrintJobStatus::Pending,
            'kitchen_ticket_id' => $ticketId,
            'printer_name' => $settings->kitchen_printer_name,
            'source' => $source,
            'attempts' => 0,
            'last_attempt_at' => now(),
        ]);
    }

    public function queueCashier(int $orderId, string $source = 'auto'): PrintJob
    {
        $settings = PosSetting::current();

        return PrintJob::query()->create([
            'kind' => PrintJobKind::CashierReceipt,
            'status' => PrintJobStatus::Pending,
            'order_id' => $orderId,
            'printer_name' => $settings->cashier_printer_name,
            'source' => $source,
            'attempts' => 0,
            'last_attempt_at' => now(),
        ]);
    }

    public function markPrinting(PrintJob $job): PrintJob
    {
        $job->forceFill([
            'status' => PrintJobStatus::Printing,
            'attempts' => $job->attempts + 1,
            'last_attempt_at' => now(),
            'error_message' => null,
        ])->save();

        return $job->fresh();
    }

    public function markSucceeded(PrintJob $job): PrintJob
    {
        $job->forceFill([
            'status' => PrintJobStatus::Succeeded,
            'succeeded_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ])->save();

        if ($job->kind === PrintJobKind::KitchenTicket && $job->kitchen_ticket_id !== null) {
            $ticket = KitchenTicket::query()->find($job->kitchen_ticket_id);

            if ($ticket !== null) {
                $this->kitchen->markPrinted($ticket);
            }
        }

        return $job->fresh();
    }

    public function markFailed(PrintJob $job, string $message): PrintJob
    {
        $job->forceFill([
            'status' => PrintJobStatus::Failed,
            'failed_at' => now(),
            'error_message' => mb_substr($message, 0, 2000),
        ])->save();

        $job = $job->fresh();

        SendPrintFailureNotifications::dispatch($job->getKey());

        return $job;
    }

    public function prepareRetry(PrintJob $job): PrintJob
    {
        $job->forceFill([
            'status' => PrintJobStatus::Pending,
            'error_message' => null,
            'failed_at' => null,
            'last_attempt_at' => now(),
        ])->save();

        return $job->fresh();
    }

    public function failedCount(): int
    {
        return PrintJob::query()->where('status', PrintJobStatus::Failed)->count();
    }

    /**
     * @return LengthAwarePaginator<int, PrintJob>
     */
    public function paginate(?string $date = null, ?string $status = null, int $perPage = 30): LengthAwarePaginator
    {
        $query = PrintJob::query()
            ->with(['kitchenTicket', 'order'])
            ->latest('id');

        if (filled($date)) {
            $query->whereDate('created_at', $date);
        }

        if (filled($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return Collection<int, PrintJob>
     */
    public function retryableFailed(int $limit = 20): Collection
    {
        return PrintJob::query()
            ->where('status', PrintJobStatus::Failed)
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
