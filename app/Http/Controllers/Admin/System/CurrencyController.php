<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\System\AdminController;

use App\Admin\Settings;
use App\Http\Requests\Admin\System\Currency\StoreCurrencyRequest;
use App\Http\Requests\Admin\System\Currency\UpdateCurrencyRequest;
use App\Models\Currency;
use App\Services\Shared\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CurrencyController extends AdminController
{
    public function __construct(
        protected CurrencyService $currencies,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('view', Settings::class);

        $items = Currency::query()
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Currency $currency) => $this->transform($currency));

        $primary = $this->currencies->primary();

        return Inertia::render('admin/settings/currencies', [
            'currencies' => $items,
            'primary' => $this->transform($primary),
            'summary' => [
                'total' => $items->count(),
                'active' => $items->where('is_active', true)->count(),
                'on_receipt' => $items->where('show_on_receipt', true)->count(),
            ],
        ]);
    }

    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $data = $request->validated();
        $data['is_primary'] = false;
        $data['rate_to_primary'] = $data['rate_to_primary'] ?? 1;
        $data['show_on_receipt'] = $request->boolean('show_on_receipt');
        $data['is_active'] = $request->boolean('is_active', true);

        Currency::query()->create($data);
        $this->currencies->forgetCache();

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.currency_created'),
        ]);
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $data = $request->validated();
        $data['show_on_receipt'] = $request->boolean('show_on_receipt');
        $data['is_active'] = $request->boolean('is_active');

        if ($currency->is_primary) {
            $data['rate_to_primary'] = 1;
            $data['show_on_receipt'] = false;
            $data['is_active'] = true;
            unset($data['code']); // keep primary code stable from form if needed
        }

        $currency->update($data);

        if ($currency->is_primary) {
            $this->currencies->syncBranchCurrency($currency->code);
        }

        $this->currencies->forgetCache();

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.currency_updated'),
        ]);
    }

    public function makePrimary(Currency $currency): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $this->currencies->makePrimary($currency);

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.currency_set_primary', ['name' => $currency->name]),
        ]);
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        if ($currency->is_primary) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => __('toast.currency_primary_delete'),
            ]);
        }

        $currency->delete();
        $this->currencies->forgetCache();

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.currency_deleted'),
        ]);
    }

    /** @return array<string, mixed> */
    private function transform(Currency $currency): array
    {
        return [
            'id' => $currency->id,
            'code' => $currency->code,
            'name' => $currency->name,
            'symbol' => $currency->symbol,
            'symbol_position' => $currency->symbol_position,
            'decimal_places' => $currency->decimal_places,
            'rate_to_primary' => (float) $currency->rate_to_primary,
            'is_primary' => (bool) $currency->is_primary,
            'show_on_receipt' => (bool) $currency->show_on_receipt,
            'is_active' => (bool) $currency->is_active,
            'sort_order' => $currency->sort_order,
            'example' => $currency->format(1234.5),
        ];
    }
}
