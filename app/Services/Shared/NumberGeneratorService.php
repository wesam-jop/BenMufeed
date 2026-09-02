<?php

namespace App\Services\Shared;

use Illuminate\Database\Eloquent\Model;

class NumberGeneratorService
{
    /**
     * Generate the next sequential document number for a model column.
     *
     * Format: {PREFIX}-{YYYYMMDD}-{0001}
     */
    public function next(string $prefix, string $model, string $column): string
    {
        /** @var class-string<Model> $model */
        $date = now()->format('Ymd');
        $pattern = "{$prefix}-{$date}-%";

        $lastNumber = $model::query()
            ->where($column, 'like', $pattern)
            ->orderByDesc($column)
            ->lockForUpdate()
            ->value($column);

        $sequence = 1;

        if ($lastNumber !== null) {
            $sequence = (int) substr($lastNumber, -4) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
