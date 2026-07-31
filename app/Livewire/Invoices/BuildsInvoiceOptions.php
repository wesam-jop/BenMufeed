<?php

namespace App\Livewire\Invoices;

use App\Livewire\Concerns\BuildsSelectOptions;
use App\Models\Material;

trait BuildsInvoiceOptions
{
    use BuildsSelectOptions;
    /**
     * @return array<int, array{value: int, label: string, search: string, meta: string}>
     */
    protected function materialOptions(): array
    {
        $priceField = $this->materialPriceField();
        $currency = config('store.currency_symbol', '$');

        return Material::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'stock_quantity', 'purchase_price', 'sale_price'])
            ->map(function (Material $material) use ($priceField, $currency) {
                $price = number_format((float) $material->{$priceField}, 2);
                $stock = number_format((float) $material->stock_quantity, 2);

                return [
                    'value' => $material->id,
                    'label' => $material->name,
                    'search' => trim("{$material->name} {$material->sku} {$material->unit} {$price}"),
                    'meta' => "{$material->sku} · {$price} {$currency} · مخزون {$stock}",
                ];
            })
            ->values()
            ->all();
    }
}
