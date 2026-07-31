<?php

namespace App\Filament\Resources\SalesInvoices\Pages;

use App\Filament\Concerns\HandlesInvoiceStock;
use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesInvoice extends CreateRecord
{
    use HandlesInvoiceStock;

    protected static string $resource = SalesInvoiceResource::class;

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();
        app(InventoryService::class)->syncSalesInvoiceStock($this->record->fresh(['items']), $this->previousInvoiceStatus());
    }
}
