<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;

use App\Filament\Concerns\HandlesInvoiceStock;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseInvoice extends CreateRecord
{
    use HandlesInvoiceStock;

    protected static string $resource = PurchaseInvoiceResource::class;

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();
        app(InventoryService::class)->syncPurchaseInvoiceStock($this->record->fresh(['items']), $this->previousInvoiceStatus());
    }
}
