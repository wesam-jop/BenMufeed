<?php

use App\Livewire\Finance\DailySalesDesk;
use App\Livewire\Finance\FinancialMovementsDesk;
use App\Livewire\Finance\PayrollDesk;
use App\Livewire\Finance\ProfitsDesk;
use App\Livewire\Invoices\PurchaseDesk;
use App\Livewire\Invoices\SalesDesk;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('invoices')->group(function (): void {
    Route::get('/purchases/{purchaseInvoice?}', PurchaseDesk::class)
        ->whereNumber('purchaseInvoice')
        ->name('invoices.purchases');

    Route::get('/sales/{salesInvoice?}', SalesDesk::class)
        ->whereNumber('salesInvoice')
        ->name('invoices.sales');

    Route::get('/daily-sales', DailySalesDesk::class)
        ->name('invoices.daily-sales');

    Route::get('/financial-movements', FinancialMovementsDesk::class)
        ->name('invoices.financial-movements');

    Route::get('/payroll', PayrollDesk::class)
        ->name('invoices.payroll');

    Route::get('/profits', ProfitsDesk::class)
        ->name('invoices.profits');
});
