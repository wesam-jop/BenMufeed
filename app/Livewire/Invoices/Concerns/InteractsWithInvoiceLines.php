<?php

namespace App\Livewire\Invoices\Concerns;

use App\Models\Material;

trait InteractsWithInvoiceLines
{
    /** @var array<int, array{key: string, material_id: ?int, material_name: string, unit: string, quantity: float, unit_price: float, line_total: float}> */
    public array $lines = [];

    abstract protected function materialPriceField(): string;

    public function addLine(): void
    {
        $this->lines[] = $this->blankLine();
    }

    public function removeLine(int $index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);

        if ($this->lines === []) {
            $this->addLine();
        }
    }

    public function updatedLines(mixed $value, string $key): void
    {
        if (! str_contains($key, '.')) {
            return;
        }

        [$index, $field] = explode('.', $key, 2);

        if (! is_numeric($index) || ! isset($this->lines[(int) $index])) {
            return;
        }

        $index = (int) $index;

        if ($field === 'material_id') {
            $this->fillLineFromMaterial($index, $value !== null && $value !== '' ? (int) $value : null);
        }

        if (in_array($field, ['quantity', 'unit_price', 'material_id'], true)) {
            $this->syncLineTotal($index);
        }
    }

    public function getLinesTotalProperty(): float
    {
        return round(collect($this->lines)->sum('line_total'), 2);
    }

    public function getLinesCountProperty(): int
    {
        return collect($this->lines)->filter(fn (array $line) => $line['material_id'] !== null)->count();
    }

    /**
     * @return array<int, array{material_id: int, material_name: string, unit: string, quantity: float, unit_price: float, line_total: float}>
     */
    protected function normalizedLines(): array
    {
        return collect($this->lines)
            ->filter(fn (array $line) => $line['material_id'] !== null)
            ->map(fn (array $line) => [
                'material_id' => (int) $line['material_id'],
                'material_name' => $line['material_name'],
                'unit' => (string) $line['unit'],
                'quantity' => round((float) $line['quantity'], 3),
                'unit_price' => round((float) $line['unit_price'], 2),
                'line_total' => round((float) $line['line_total'], 2),
            ])
            ->values()
            ->all();
    }

    protected function fillLineFromMaterial(int $index, ?int $materialId): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        if ($materialId === null) {
            $this->lines[$index]['material_id'] = null;
            $this->lines[$index]['material_name'] = '';
            $this->lines[$index]['unit'] = '';
            $this->lines[$index]['line_total'] = 0;

            return;
        }

        $material = Material::query()
            ->where('is_active', true)
            ->find($materialId);

        if ($material === null) {
            return;
        }

        $priceField = $this->materialPriceField();

        $this->lines[$index]['material_id'] = $material->getKey();
        $this->lines[$index]['material_name'] = $material->name;
        $this->lines[$index]['unit'] = (string) $material->unit;
        $this->lines[$index]['unit_price'] = (float) $material->{$priceField};
        $this->syncLineTotal($index);
    }

    protected function syncLineTotal(int $index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $qty = (float) ($this->lines[$index]['quantity'] ?? 0);
        $price = (float) ($this->lines[$index]['unit_price'] ?? 0);

        $this->lines[$index]['line_total'] = round($qty * $price, 2);
    }

    protected function loadLinesFromInvoiceItems(iterable $items): void
    {
        $this->lines = [];

        foreach ($items as $item) {
            if ($item->material_id === null) {
                continue;
            }

            $this->lines[] = [
                'key' => 'item-'.$item->getKey(),
                'material_id' => $item->material_id,
                'material_name' => $item->material_name,
                'unit' => (string) $item->unit,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ];
        }

        if ($this->lines === []) {
            $this->addLine();
        }
    }

    protected function clearLines(): void
    {
        $this->lines = [];
        $this->addLine();
    }

    /**
     * @return array{key: string, material_id: null, material_name: string, unit: string, quantity: float, unit_price: float, line_total: float}
     */
    protected function blankLine(): array
    {
        return [
            'key' => uniqid('line-', true),
            'material_id' => null,
            'material_name' => '',
            'unit' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'line_total' => 0,
        ];
    }

    protected function ensureInitialLine(): void
    {
        if ($this->lines === []) {
            $this->addLine();
        }
    }

    protected function pruneEmptyLines(): void
    {
        $this->lines = collect($this->lines)
            ->filter(fn (array $line) => $line['material_id'] !== null)
            ->values()
            ->all();
    }
}
