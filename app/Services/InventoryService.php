<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Material;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function reversePurchaseInvoice(PurchaseInvoice $invoice): void
    {
        if (! $invoice->stock_applied) {
            return;
        }

        DB::transaction(function () use ($invoice): void {
            $invoice->loadMissing('items.material');
            $this->reversePurchaseStock($invoice);
            $invoice->forceFill(['stock_applied' => false])->saveQuietly();
        });
    }

    public function reverseSalesInvoice(SalesInvoice $invoice): void
    {
        if (! $invoice->stock_applied) {
            return;
        }

        DB::transaction(function () use ($invoice): void {
            $invoice->loadMissing('items.material');
            $this->reverseSalesStock($invoice);
            $invoice->forceFill(['stock_applied' => false])->saveQuietly();
        });
    }

    public function syncPurchaseInvoiceStock(PurchaseInvoice $invoice, InvoiceStatus $previousStatus): void
    {
        DB::transaction(function () use ($invoice, $previousStatus): void {
            $invoice->loadMissing('items.material');

            if ($previousStatus === InvoiceStatus::Confirmed && $invoice->stock_applied) {
                $this->reversePurchaseStock($invoice);
                $invoice->forceFill(['stock_applied' => false])->saveQuietly();
            }

            if ($invoice->status === InvoiceStatus::Confirmed && ! $invoice->stock_applied) {
                $this->applyPurchaseStock($invoice);
                $invoice->forceFill(['stock_applied' => true])->saveQuietly();
            }
        });
    }

    public function syncSalesInvoiceStock(SalesInvoice $invoice, InvoiceStatus $previousStatus): void
    {
        DB::transaction(function () use ($invoice, $previousStatus): void {
            $invoice->loadMissing('items.material');

            if ($previousStatus === InvoiceStatus::Confirmed && $invoice->stock_applied) {
                $this->reverseSalesStock($invoice);
                $invoice->forceFill(['stock_applied' => false])->saveQuietly();
            }

            if ($invoice->status === InvoiceStatus::Confirmed && ! $invoice->stock_applied) {
                $this->applySalesStock($invoice);
                $invoice->forceFill(['stock_applied' => true])->saveQuietly();
            }
        });
    }

    protected function applyPurchaseStock(PurchaseInvoice $invoice): void
    {
        foreach ($invoice->items as $item) {
            if ($item->material_id === null) {
                continue;
            }

            $material = Material::query()->lockForUpdate()->find($item->material_id);

            if ($material === null) {
                continue;
            }

            $material->increment('stock_quantity', (float) $item->quantity);

            if ((float) $item->unit_price > 0) {
                $material->update(['purchase_price' => $item->unit_price]);
            }
        }
    }

    protected function reversePurchaseStock(PurchaseInvoice $invoice): void
    {
        foreach ($invoice->items as $item) {
            if ($item->material_id === null) {
                continue;
            }

            $material = Material::query()->lockForUpdate()->find($item->material_id);

            if ($material === null) {
                continue;
            }

            $material->decrement('stock_quantity', (float) $item->quantity);
        }
    }

    protected function applySalesStock(SalesInvoice $invoice): void
    {
        foreach ($invoice->items as $item) {
            if ($item->material_id === null) {
                continue;
            }

            $material = Material::query()->lockForUpdate()->find($item->material_id);

            if ($material === null) {
                continue;
            }

            $qty = (float) $item->quantity;

            if ((float) $material->stock_quantity < $qty) {
                throw ValidationException::withMessages([
                    'items' => "الكمية غير كافية للمادة: {$material->name} (المتوفر: {$material->stock_quantity}).",
                ]);
            }

            $material->decrement('stock_quantity', $qty);
        }
    }

    protected function reverseSalesStock(SalesInvoice $invoice): void
    {
        foreach ($invoice->items as $item) {
            if ($item->material_id === null) {
                continue;
            }

            $material = Material::query()->lockForUpdate()->find($item->material_id);

            if ($material === null) {
                continue;
            }

            $material->increment('stock_quantity', (float) $item->quantity);
        }
    }
}
