<?php

use App\Http\Controllers\Print\PrintController;
use App\Http\Controllers\Print\PrintJobController;
use App\Http\Controllers\Print\QzController;
use App\Http\Controllers\Restaurant\TableQrController;
use App\Livewire\Pos\Cashier;
use App\Livewire\Pos\PosInvoiceHistory;
use App\Livewire\Pos\PosPrintLog;
use App\Livewire\Pos\PosPrinterSetup;
use App\Livewire\Pos\PosTables;
use App\Livewire\Pos\PosWebsiteOrders;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'pos.access'])->get('/api/print/pending-jobs', [PrintJobController::class, 'pendingForBrowser'])
    ->name('api.print.pending-jobs');

Route::middleware(['auth', 'printing'])->get('/api/print/failed-count', function () {
    return response()->json([
        'count' => app(\App\Services\PrintJobService::class)->failedCount(),
    ]);
})->name('api.print.failed-count');

Route::middleware(['auth', 'printing'])->group(function (): void {
    Route::get('/qz/certificate', [QzController::class, 'certificate'])->name('qz.certificate');
    Route::post('/qz/sign', [QzController::class, 'sign'])->name('qz.sign');

    Route::get('/print/kitchen-ticket/{ticket}', [PrintController::class, 'kitchenTicket'])
        ->name('print.kitchen-ticket');
    Route::get('/print/cashier-receipt/{order}', [PrintController::class, 'cashierReceipt'])
        ->name('print.cashier-receipt');

    Route::post('/print/jobs/kitchen/{ticket}', [PrintJobController::class, 'queueKitchen'])
        ->name('print.jobs.kitchen');
    Route::post('/print/jobs/cashier/{order}', [PrintJobController::class, 'queueCashier'])
        ->name('print.jobs.cashier');
    Route::post('/print/jobs/{printJob}/printing', [PrintJobController::class, 'markPrinting'])
        ->name('print.jobs.printing');
    Route::post('/print/jobs/{printJob}/succeeded', [PrintJobController::class, 'markSucceeded'])
        ->name('print.jobs.succeeded');
    Route::post('/print/jobs/{printJob}/failed', [PrintJobController::class, 'markFailed'])
        ->name('print.jobs.failed');

    Route::get('/pos/printing', PosPrinterSetup::class)->name('pos.printing');
});

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/admin/tables/venue-qr.png', [TableQrController::class, 'venue'])
        ->name('tables.qr.venue');
    Route::get('/admin/tables/{table}/qr.png', [TableQrController::class, 'single'])
        ->name('tables.qr.png');
    Route::get('/admin/tables/qr-all.zip', [TableQrController::class, 'all'])
        ->name('tables.qr.all');
    Route::redirect('/admin/tables/{table}/qr.pdf', '/admin/tables/{table}/qr.png');
    Route::redirect('/admin/tables/qr-all.pdf', '/admin/tables/qr-all.zip');
});

Route::middleware(['auth', 'pos.access'])->prefix('pos')->group(function (): void {
    Route::get('/', Cashier::class)->name('pos.cashier');
    Route::get('/invoices', PosInvoiceHistory::class)->name('pos.invoices');
    Route::get('/invoices/{order}/edit', PosInvoiceHistory::class)->name('pos.invoices.edit');
    Route::get('/print-log', PosPrintLog::class)->name('pos.print-log');
    Route::get('/website-orders', PosWebsiteOrders::class)->name('pos.website-orders');
    Route::get('/tables', PosTables::class)->name('pos.tables');
});
