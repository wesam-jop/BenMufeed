<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PosSetting extends Model
{
    protected $fillable = [
        'kitchen_printer_name',
        'cashier_printer_name',
        'auto_print_kitchen',
        'auto_print_cashier',
        'kitchen_alert_sound',
        'receipt_show_logo',
        'receipt_paper_width_mm',
        'receipt_business_name',
        'receipt_header',
        'receipt_footer',
        'kitchen_ticket_footer',
        'delivery_fee',
    ];

    protected function casts(): array
    {
        return [
            'auto_print_kitchen' => 'boolean',
            'auto_print_cashier' => 'boolean',
            'kitchen_alert_sound' => 'boolean',
            'receipt_show_logo' => 'boolean',
            'receipt_paper_width_mm' => 'integer',
            'delivery_fee' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        $id = Cache::remember('pos_settings.current_id', 3600, function (): int {
            return static::query()->firstOrCreate(
                ['id' => 1],
                [
                    'auto_print_kitchen' => true,
                    'auto_print_cashier' => true,
                    'kitchen_alert_sound' => true,
                    'receipt_show_logo' => true,
                    'receipt_paper_width_mm' => 80,
                    'delivery_fee' => 0,
                ],
            )->getKey();
        });

        return static::query()->findOrFail($id);
    }

    public static function forgetCache(): void
    {
        Cache::forget('pos_settings.current');
        Cache::forget('pos_settings.current_id');
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            static::forgetCache();
            \App\Support\StorefrontCache::forgetDeliveryFee();
        });
    }

    public function businessName(): string
    {
        if (filled($this->receipt_business_name)) {
            return $this->receipt_business_name;
        }

        return SiteSetting::current()->site_name ?? config('app.name');
    }
}
