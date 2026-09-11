<?php

namespace App\Services\Shared;

use App\Models\Branch;
use App\Models\Currency;
use Illuminate\Support\Collection;

class CurrencyService
{
    public function primary(): Currency
    {
        $primary = $this->active()->firstWhere('is_primary', true);

        if ($primary) {
            return $primary;
        }

        return Currency::query()->firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'دولار أمريكي',
                'symbol' => '$',
                'symbol_position' => 'before',
                'decimal_places' => 2,
                'rate_to_primary' => 1,
                'is_primary' => true,
                'show_on_receipt' => false,
                'is_active' => true,
                'sort_order' => 0,
            ],
        );
    }

    /**
     * @return Collection<int, Currency>
     */
    public function active(): Collection
    {
        // Do not cache Eloquent models — database/file cache can return __PHP_Incomplete_Class.
        return Currency::query()
            ->active()
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function forgetCache(): void
    {
        // Kept for callers after currency updates; no model cache currently.
    }

    /**
     * Shared frontend money config (primary currency display).
     *
     * @return array<string, mixed>
     */
    public function moneyConfig(): array
    {
        $primary = $this->primary();

        return [
            'code' => $primary->code,
            'name' => $primary->name,
            'symbol' => $primary->symbol,
            'symbol_position' => $primary->symbol_position,
            'decimal_places' => $primary->decimal_places,
        ];
    }

    /**
     * Secondary currencies to print on receipts.
     *
     * @return list<array{code: string, name: string, symbol: string, symbol_position: string, decimal_places: int, rate_to_primary: float}>
     */
    public function receiptCurrencies(): array
    {
        return $this->active()
            ->where('is_primary', false)
            ->where('show_on_receipt', true)
            ->values()
            ->map(fn (Currency $currency) => [
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'symbol_position' => $currency->symbol_position,
                'decimal_places' => $currency->decimal_places,
                'rate_to_primary' => (float) $currency->rate_to_primary,
            ])
            ->all();
    }

    /**
     * @return list<array{code: string, name: string, symbol: string, amount: float, formatted: string}>
     */
    public function convertForReceipt(float $amountInPrimary): array
    {
        return $this->active()
            ->where('is_primary', false)
            ->where('show_on_receipt', true)
            ->values()
            ->map(function (Currency $currency) use ($amountInPrimary) {
                $converted = $currency->convertFromPrimary($amountInPrimary);

                return [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'amount' => $converted,
                    'formatted' => $currency->format($converted),
                ];
            })
            ->all();
    }

    public function syncBranchCurrency(string $code): void
    {
        Branch::query()->where('is_active', true)->update(['currency' => $code]);
    }

    public function makePrimary(Currency $currency): void
    {
        Currency::query()->where('is_primary', true)->update(['is_primary' => false]);

        Currency::query()->whereKey($currency->getKey())->update([
            'is_primary' => true,
            'rate_to_primary' => 1,
            'is_active' => true,
            'show_on_receipt' => false,
        ]);

        $currency->refresh();

        $this->syncBranchCurrency($currency->code);
        $this->forgetCache();
    }
}
