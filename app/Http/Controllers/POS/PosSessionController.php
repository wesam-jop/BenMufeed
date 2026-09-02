<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Services\Accounting\CashRegisterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PosSessionController extends Controller
{
    public function __construct(
        private CashRegisterService $cashRegisterService,
    ) {}

    public function showDailySummary(Request $request): Response|RedirectResponse
    {
        try {
            $session = $this->cashRegisterService->resolveTodaySession($request->user(), createIfMissing: true);
            $invoices = $this->cashRegisterService->dailyInvoices($session);

            return Inertia::render('pos/close-session', [
                'session' => $this->cashRegisterService->dailySummary($session),
                'invoices' => $invoices->map(function ($invoice) {
                    $payment = $invoice->payments->first();

                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'total' => (float) $invoice->total,
                        'paid' => (float) $invoice->paid,
                        'status' => $invoice->status->value ?? $invoice->status,
                        'items_count' => $invoice->items_count ?? $invoice->items()->count(),
                        'payment_method' => $payment?->method?->value ?? 'cash',
                        'cashier_name' => $invoice->user?->name,
                        'created_at' => $invoice->created_at?->toIso8601String(),
                    ];
                }),
            ]);
        } catch (RuntimeException $e) {
            return redirect()->route('pos.terminal')
                ->with('toast', ['type' => 'warning', 'message' => $e->getMessage()]);
        }
    }

    /** @deprecated إغلاق الجرد أُلغي — يُعاد التوجيه لملخص اليوم */
    public function closeToday(Request $request): RedirectResponse
    {
        return redirect()->route('pos.sessions.daily-summary');
    }

    /** @deprecated */
    public function showCloseForm(Request $request): Response|RedirectResponse
    {
        return $this->showDailySummary($request);
    }

    /** @deprecated Manual open removed — daily sessions are automatic */
    public function open(Request $request): RedirectResponse
    {
        return redirect()->route('pos.terminal');
    }

    /** @deprecated إغلاق الجرد أُلغي */
    public function close(Request $request, $session): RedirectResponse
    {
        return redirect()->route('pos.sessions.daily-summary');
    }
}
