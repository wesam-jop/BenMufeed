<?php

namespace App\Http\Controllers\Print;

use App\Http\Controllers\Controller;
use App\Models\KitchenTicket;
use App\Models\Order;
use App\Services\KitchenService;
use App\Services\ReceiptPrintService;
use App\Support\PrintableOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintController extends Controller
{
    public function kitchenTicket(KitchenTicket $ticket, KitchenService $kitchen, Request $request): View
    {
        if (! $request->boolean('silent')) {
            $kitchen->markPrinted($ticket);
        }

        return view('print.kitchen-ticket', array_merge(
            ReceiptPrintService::make()->kitchenTicketData($ticket),
            ['silent' => $request->boolean('silent')],
        ));
    }

    public function cashierReceipt(Order $order, Request $request): View
    {
        abort_unless(PrintableOrder::canPrintCashierReceipt($order), 404);

        return view('print.cashier-receipt', array_merge(
            ReceiptPrintService::make()->cashierReceiptData($order),
            ['silent' => $request->boolean('silent')],
        ));
    }
}
