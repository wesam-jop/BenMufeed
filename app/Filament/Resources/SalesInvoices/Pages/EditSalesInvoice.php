<?php

namespace App\Filament\Resources\SalesInvoices\Pages;

use App\Filament\Concerns\HandlesInvoiceStock;
use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Services\InventoryService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSalesInvoice extends EditRecord
{
    use HandlesInvoiceStock;

    protected static string $resource = SalesInvoiceResource::class;

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
        app(InventoryService::class)->syncSalesInvoiceStock($this->record->fresh(['items']), $this->previousInvoiceStatus());
    }
}
