<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;

use App\Filament\Concerns\HandlesInvoiceStock;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Services\InventoryService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseInvoice extends EditRecord
{
    use HandlesInvoiceStock;

    protected static string $resource = PurchaseInvoiceResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->rememberInvoiceStatus();
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
        app(InventoryService::class)->syncPurchaseInvoiceStock($this->record->fresh(['items']), $this->previousInvoiceStatus());
    }
}
