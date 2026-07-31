<?php

namespace App\Filament\Concerns;

use App\Enums\InvoiceStatus;

trait HandlesInvoiceStock
{
    protected ?InvoiceStatus $previousInvoiceStatus = null;

    protected function rememberInvoiceStatus(): void
    {
        if ($this->record !== null) {
            $this->previousInvoiceStatus = $this->record->status instanceof InvoiceStatus
                ? $this->record->status
                : InvoiceStatus::from((string) $this->record->status);
        }
    }

    protected function previousInvoiceStatus(): InvoiceStatus
    {
        return $this->previousInvoiceStatus ?? InvoiceStatus::Draft;
    }
}
