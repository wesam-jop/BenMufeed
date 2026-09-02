<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Admin\System\AdminController;

use App\Admin\Settings;
use App\Http\Requests\Admin\System\Settings\UpdateBusinessSettingsRequest;
use App\Services\Shared\BusinessSettingsService;
use App\Services\Shared\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends AdminController
{
    public function __construct(private BusinessSettingsService $businessSettings) {}

    public function index(Request $request): Response
    {
        $this->authorize('view', Settings::class);

        $settings = $this->businessSettings->all();

        return Inertia::render('admin/settings/business', [
            'settings' => $settings,
            'summary' => $this->buildSummary($settings),
            'options' => [
                'currencies' => $this->currencyOptions(),
                'timezones' => $this->timezoneOptions(),
            ],
        ]);
    }

    public function update(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        $this->authorize('update', Settings::class);

        $this->businessSettings->merge($request->validated());

        return back()->with('toast', [
            'type' => 'success',
            'message' => __('toast.business_settings_saved'),
        ]);
    }

    /** @param  array<string, mixed>  $settings */
    private function buildSummary(array $settings): array
    {
        $contactFields = ['phone', 'email', 'address', 'whatsapp'];
        $filledContact = collect($contactFields)
            ->filter(fn (string $field) => filled($settings[$field] ?? null))
            ->count();

        $coreFields = ['business_name', 'description', 'opening_hours', 'tax_number'];
        $filledCore = collect($coreFields)
            ->filter(fn (string $field) => filled($settings[$field] ?? null))
            ->count();

        return [
            'contact_fields_filled' => $filledContact,
            'core_fields_filled' => $filledCore,
            'has_tax_number' => filled($settings['tax_number'] ?? null),
            'has_receipt_footer' => filled($settings['receipt_footer'] ?? null),
            'currency' => $settings['currency'] ?? 'SAR',
            'timezone' => $settings['timezone'] ?? 'Asia/Riyadh',
            'updated_at' => $settings['updated_at'] ?? null,
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function currencyOptions(): array
    {
        return app(CurrencyService::class)
            ->active()
            ->map(fn ($currency) => [
                'value' => $currency->code,
                'label' => $currency->name.' ('.$currency->code.')',
            ])
            ->values()
            ->all();
    }

    /** @return list<array{value: string, label: string}> */
    private function timezoneOptions(): array
    {
        return [
            ['value' => 'Asia/Riyadh', 'label' => 'الرياض (Asia/Riyadh)'],
            ['value' => 'Asia/Dubai', 'label' => 'دبي (Asia/Dubai)'],
            ['value' => 'Asia/Kuwait', 'label' => 'الكويت (Asia/Kuwait)'],
            ['value' => 'Africa/Cairo', 'label' => 'القاهرة (Africa/Cairo)'],
        ];
    }
}
