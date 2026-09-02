<?php

namespace App\Services\Shared;

use App\Models\Branch;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BusinessSettingsService
{
    public const CACHE_KEY = 'business_settings';

    /** @return list<string> */
    public static function sectionTypes(): array
    {
        return [
            'brand',
            'header_meta',
            'invoice_meta',
            'items',
            'totals',
            'currency_conversions',
            'payment',
            'custom_text',
            'divider',
            'spacer',
        ];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $cached = Cache::get(self::CACHE_KEY, []);

        return array_merge($this->defaults(), is_array($cached) ? $cached : []);
    }

    /**
     * @param  array<string, mixed>  $partial
     * @return array<string, mixed>
     */
    public function merge(array $partial): array
    {
        $next = [
            ...$this->all(),
            ...$partial,
            'updated_at' => now()->toIso8601String(),
        ];

        Cache::forever(self::CACHE_KEY, $next);

        return $next;
    }

    /** @return array{paper_width_mm: int, sections: list<array<string, mixed>>} */
    public function receiptTemplate(): array
    {
        $settings = $this->all();
        $template = $settings['receipt_template'] ?? null;

        if (! is_array($template) || ! isset($template['sections']) || ! is_array($template['sections'])) {
            return $this->defaultReceiptTemplate($settings['receipt_footer'] ?? 'شكراً لزيارتكم');
        }

        return [
            'paper_width_mm' => in_array((int) ($template['paper_width_mm'] ?? 0), [0, 58, 80, 210], true)
                ? (int) ($template['paper_width_mm'] ?? 0)
                : 0,
            'sections' => array_values(array_map(
                fn ($section) => $this->normalizeSection(is_array($section) ? $section : []),
                $template['sections'],
            )),
        ];
    }

    /**
     * @param  array{paper_width_mm?: int, sections: list<array<string, mixed>>}  $template
     * @return array{paper_width_mm: int, sections: list<array<string, mixed>>}
     */
    public function saveReceiptTemplate(array $template): array
    {
        $width = (int) ($template['paper_width_mm'] ?? 0);
        if (! in_array($width, [0, 58, 80, 210], true)) {
            $width = 0;
        }

        $normalized = [
            'paper_width_mm' => $width,
            'sections' => array_values(array_map(
                fn ($section) => $this->normalizeSection(is_array($section) ? $section : []),
                $template['sections'] ?? [],
            )),
        ];

        $this->merge(['receipt_template' => $normalized]);

        return $normalized;
    }

    /** @return array{paper_width_mm: int, sections: list<array<string, mixed>>} */
    public function defaultReceiptTemplate(?string $footer = null): array
    {
        $footerText = $footer ?? 'شكراً لزيارتكم';

        return [
            'paper_width_mm' => 0,
            'sections' => [
                $this->makeSection('brand'),
                $this->makeSection('header_meta'),
                $this->makeSection('divider'),
                $this->makeSection('invoice_meta'),
                $this->makeSection('divider'),
                $this->makeSection('items', ['show_unit_price' => false]),
                $this->makeSection('divider'),
                $this->makeSection('totals'),
                $this->makeSection('currency_conversions'),
                $this->makeSection('payment'),
                $this->makeSection('spacer'),
                $this->makeSection('custom_text', [
                    'text' => $footerText,
                    'align' => 'center',
                    'size' => 'sm',
                ]),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array{id: string, type: string, enabled: bool, props: array<string, mixed>}
     */
    public function makeSection(string $type, array $props = []): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'enabled' => true,
            'props' => $props,
        ];
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array{id: string, type: string, enabled: bool, props: array<string, mixed>}
     */
    private function normalizeSection(array $section): array
    {
        $type = (string) ($section['type'] ?? 'custom_text');
        if (! in_array($type, self::sectionTypes(), true)) {
            $type = 'custom_text';
        }

        $props = is_array($section['props'] ?? null) ? $section['props'] : [];

        return [
            'id' => filled($section['id'] ?? null) ? (string) $section['id'] : (string) Str::uuid(),
            'type' => $type,
            'enabled' => (bool) ($section['enabled'] ?? true),
            'props' => $props,
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        $site = SiteSetting::current();
        $branch = Branch::query()->where('is_active', true)->orderBy('id')->first();

        return [
            'business_name' => $site->business_name ?? $branch?->name ?? '',
            'description' => $site->tagline ?? '',
            'opening_hours' => $site->opening_hours ?? '',
            'phone' => $site->phone ?? $branch?->phone ?? '',
            'email' => $site->email ?? $branch?->email ?? '',
            'address' => $site->address ?? $branch?->address ?? '',
            'whatsapp' => $site->whatsapp ?? '',
            'instagram' => $site->instagram ?? '',
            'tax_number' => $site->tax_number ?? '',
            'currency' => app(CurrencyService::class)->primary()->code,
            'timezone' => $branch?->timezone ?? 'Asia/Riyadh',
            'receipt_footer' => 'شكراً لزيارتكم',
            'receipt_template' => null,
            'updated_at' => null,
        ];
    }

    /** @return array{name: string, logo: ?string, tagline: ?string, phone: ?string, address: ?string, tax_number: ?string} */
    public function receiptBusiness(): array
    {
        $settings = $this->all();
        $site = SiteSetting::current();
        $info = app(StorefrontService::class)->businessInfo();

        return [
            'name' => $info['name'] ?: ($settings['business_name'] ?? config('app.name')),
            'logo' => $info['logo'] ?? null,
            'tagline' => $info['tagline'] ?? ($settings['description'] ?? null),
            'phone' => $settings['phone'] ?? $site->phone,
            'address' => $settings['address'] ?? $site->address,
            'tax_number' => $settings['tax_number'] ?? $site->tax_number,
        ];
    }
}
