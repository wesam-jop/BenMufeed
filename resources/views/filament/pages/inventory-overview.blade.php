<x-filament-panels::page>
    @php($symbol = config('store.currency_symbol', '$'))

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-filament::section>
            <div class="space-y-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">قيمة المخزون الحالي</p>
                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                    {{ number_format($this->totalStockValue, 2) }} {{ $symbol }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">بسعر الشراء × الكمية المتوفرة</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="space-y-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">عدد المواد النشطة</p>
                <p class="text-2xl font-bold">{{ $this->activeMaterialsCount }}</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="space-y-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">مواد منخفضة المخزون</p>
                <p @class([
                    'text-2xl font-bold',
                    'text-danger-600 dark:text-danger-400' => $this->lowStockCount > 0,
                ])>
                    {{ $this->lowStockCount }}
                </p>
            </div>
        </x-filament::section>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
