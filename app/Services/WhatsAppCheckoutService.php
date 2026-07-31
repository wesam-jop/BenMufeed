<?php

namespace App\Services;

use App\Repositories\Contracts\SiteSettingRepositoryInterface;

class WhatsAppCheckoutService
{
    public function __construct(
        protected SiteSettingRepositoryInterface $settings,
    ) {}

    /**
     * @param  array<int, array{name: string, quantity: int, unit_price: float, line_total: float}>  $lines
     */
    public function formatOrderMessage(
        array $lines,
        float $total,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?string $customerNote = null,
    ): string {
        $symbol = config('store.currency_symbol', '$');
        $body = "مرحباً، أريد إتمام الطلب التالي:\n\n";

        if ($customerName) {
            $body .= "الاسم: {$customerName}\n";
        }

        if ($customerPhone) {
            $body .= "الهاتف: {$customerPhone}\n";
        }

        if ($customerName || $customerPhone) {
            $body .= "\n";
        }

        $body .= "الأطباق:\n";

        foreach ($lines as $line) {
            $unit = number_format($line['unit_price'], 2, '.', '');
            $lineTotal = number_format($line['line_total'], 2, '.', '');
            $body .= sprintf(
                "• %s × %d — %s %s (السطر: %s %s)\n",
                $line['name'],
                $line['quantity'],
                $unit,
                $symbol,
                $lineTotal,
                $symbol,
            );
        }

        $body .= "\n*الإجمالي: ".number_format($total, 2, '.', '')." {$symbol}*\n";

        if ($customerNote) {
            $body .= "\nملاحظة: {$customerNote}\n";
        }

        return rtrim($body);
    }

    public function checkoutUrl(string $message): string
    {
        $phone = preg_replace('/\D+/', '', $this->settings->current()->whatsapp_phone) ?? '';

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}
