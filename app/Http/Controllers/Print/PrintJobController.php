<?php

namespace App\Http\Controllers\Print;

use App\Http\Controllers\Controller;
use App\Enums\PrintJobStatus;
use App\Models\KitchenTicket;
use App\Models\Order;
use App\Models\PrintJob;
use App\Services\PrintJobService;
use App\Support\PrintableOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintJobController extends Controller
{
    public function pendingForBrowser(): JsonResponse
    {
        $jobs = PrintJob::query()
            ->where('status', PrintJobStatus::Pending)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->orderBy('id')
            ->limit(8)
            ->get();

        return response()->json([
            'jobs' => $jobs->map(fn (PrintJob $job): array => [
                'printJobId' => $job->getKey(),
                'kind' => $job->kind->value,
                'ticketId' => $job->kitchen_ticket_id,
                'orderId' => $job->order_id,
                'url' => $job->printUrl(),
                'printer' => $job->printer_name,
            ])->values(),
        ]);
    }

    public function queueKitchen(KitchenTicket $ticket, PrintJobService $jobs): JsonResponse
    {
        $job = $jobs->queueKitchen($ticket->getKey(), 'manual');

        return response()->json($this->jobPayload($job));
    }

    public function queueCashier(Order $order, PrintJobService $jobs): JsonResponse
    {
        abort_unless(PrintableOrder::canPrintCashierReceipt($order), 404);

        $job = $jobs->queueCashier($order->getKey(), 'manual');

        return response()->json($this->jobPayload($job));
    }

    public function markPrinting(PrintJob $printJob, PrintJobService $jobs): JsonResponse
    {
        $jobs->markPrinting($printJob);

        return response()->json(['ok' => true]);
    }

    public function markSucceeded(PrintJob $printJob, PrintJobService $jobs): JsonResponse
    {
        $jobs->markSucceeded($printJob);

        return response()->json(['ok' => true]);
    }

    public function markFailed(PrintJob $printJob, Request $request, PrintJobService $jobs): JsonResponse
    {
        $message = (string) ($request->input('message') ?? $request->getContent() ?? 'فشلت الطباعة.');

        $jobs->markFailed($printJob, $message !== '' ? $message : 'فشلت الطباعة.');

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function jobPayload(PrintJob $job): array
    {
        return [
            'printJobId' => $job->getKey(),
            'url' => $job->printUrl(),
            'printer' => $job->printer_name,
            'kind' => $job->kind->value,
        ];
    }
}
