<?php

namespace App\Services;

use App\Enums\PosServiceType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SiteSetting;
use App\Repositories\Contracts\SiteSettingRepositoryInterface;

class DeliveryOrderWhatsAppService
{
    public function __construct(
        protected WhatsAppService $whatsapp,
        protected SiteSettingRepositoryInterface $settings,
    ) {}

    public function notifyStoreNewOrder(Order $order): bool
    {
        if (! in_array($order->service_type, [PosServiceType::Delivery, PosServiceType::Takeaway], true)) {
            return false;
        }

        $storePhone = $this->storeWhatsAppPhone();

        if ($storePhone === null) {
            return false;
        }

        $order->loadMissing(['items.addons', 'savedAddress']);

        return $this->whatsapp->sendText(
            $storePhone,
            $this->buildStoreNewOrderMessage($order),
            $order->service_type === PosServiceType::Delivery ? 'delivery_order_store' : 'takeaway_order_store',
        );
    }

    public function notifyCustomerPending(Order $order): bool
    {
        if ($order->service_type !== PosServiceType::Delivery) {
            return false;
        }

        $customerPhone = trim((string) ($order->customer_phone ?? ''));

        if ($customerPhone === '') {
            return false;
        }

        return $this->whatsapp->sendText(
            $customerPhone,
            $this->buildCustomerPendingMessage($order),
            'delivery_order_customer_pending',
        );
    }

    public function notifyCustomerAccepted(Order $order): bool
    {
        if ($order->service_type !== PosServiceType::Delivery) {
            return false;
        }

        $customerPhone = trim((string) ($order->customer_phone ?? ''));

        if ($customerPhone === '') {
            return false;
        }

        $order->loadMissing(['items.addons']);

        return $this->whatsapp->sendText(
            $customerPhone,
            $this->buildCustomerAcceptedMessage($order),
            'delivery_order_customer',
        );
    }

    public function buildStoreNewOrderMessage(Order $order): string
    {
        $siteName = SiteSetting::current()->site_name ?: config('app.name');
        $symbol = config('store.currency_symbol', '$');
        $customerName = trim((string) ($order->customer_name ?? '')) ?: 'زبون';
        $customerPhoneDigits = preg_replace('/\D+/', '', (string) ($order->customer_phone ?? '')) ?? '';
        $customerPhone = $this->formatDisplayPhone((string) ($order->customer_phone ?? ''));
        $isDelivery = $order->service_type === PosServiceType::Delivery;
        $locationNote = trim((string) ($order->delivery_location_note ?? ''));
        $mapUrl = $order->deliveryMapUrl();
        $orderTitle = $isDelivery ? 'طلب توصيل جديد' : 'طلب سفري جديد';

        $lines = [
            ($isDelivery ? '🛵' : '🛍')." *{$orderTitle} — {$siteName}*",
            '',
            '📋 *رقم الطلب:* #'.$order->getKey(),
            '👤 *الزبون:* '.$customerName,
            '📞 *هاتف الزبون:* '.($customerPhone !== '' ? $customerPhone : '—'),
        ];

        if ($customerPhoneDigits !== '') {
            $lines[] = '💬 *واتساب الزبون:* https://wa.me/'.$customerPhoneDigits;
        }

        if ($isDelivery) {
            $savedLabel = trim((string) ($order->savedAddress?->label ?? ''));

            if ($savedLabel !== '') {
                $lines[] = '🏷 *اسم الموقع المحفوظ:* '.$savedLabel;
            }

            if ($locationNote !== '') {
                $lines[] = '📍 *وصف الموقع:* '.$locationNote;
            }

            if ($mapUrl !== null) {
                $lines[] = '🗺 *موقع التوصيل على Google Maps:*';
                $lines[] = $mapUrl;
            } elseif ($locationNote !== '') {
                $lines[] = '⚠️ *تنبيه:* لم يُحدَّد موقع على الخريطة — راجع وصف الموقع أعلاه.';
            }
        }

        $lines[] = '';
        $lines[] = '━━━━━━';
        $lines[] = '🍽 *تفاصيل الطلب:*';
        $lines[] = $this->formatOrderItems($order, $symbol);
        $lines[] = '';
        $lines[] = $this->formatTotals($order, $symbol);

        $note = trim((string) ($order->customer_note ?? ''));

        if ($note !== '') {
            $lines[] = '';
            $lines[] = '📝 *ملاحظة الزبون:* '.$note;
        }

        $lines[] = '';
        $lines[] = '⏰ *وقت الطلب:* '.($order->created_at?->format('Y-m-d H:i') ?? '—');

        return implode("\n", $lines);
    }

    public function buildCustomerPendingMessage(Order $order): string
    {
        $siteName = SiteSetting::current()->site_name ?: config('app.name');
        $customerName = trim((string) ($order->customer_name ?? '')) ?: 'عزيزنا';

        $lines = [
            "📦 *{$siteName}*",
            '',
            "مرحباً {$customerName}،",
            'وصل طلب التوصيل رقم #'.$order->getKey().' بنجاح ✅',
            '',
            '⏳ طلبك *بانتظار الموافقة* من المطبخ.',
            'سنبلغك فور قبول الطلب وبدء التحضير.',
            '',
            'شكراً لصبرك! ☕',
        ];

        return implode("\n", $lines);
    }

    public function buildCustomerAcceptedMessage(Order $order): string
    {
        $siteName = SiteSetting::current()->site_name ?: config('app.name');
        $symbol = config('store.currency_symbol', '$');
        $customerName = trim((string) ($order->customer_name ?? '')) ?: 'عزيزنا';
        $locationNote = trim((string) ($order->delivery_location_note ?? ''));

        $lines = [
            "✅ *تم استلام طلبك — {$siteName}*",
            '',
            "مرحباً {$customerName}،",
            'طلب التوصيل رقم #'.$order->getKey().' *قيد التحضير* الآن!',
            '',
            '🍽 *تفاصيل الطلب:*',
            $this->formatOrderItems($order, $symbol),
            '',
            $this->formatTotals($order, $symbol),
        ];

        if ($locationNote !== '') {
            $lines[] = '';
            $lines[] = '📍 *عنوان التوصيل:* '.$locationNote;
        }

        $lines[] = '';
        $lines[] = 'سنوصل طلبك إليك في أقرب وقت.';
        $lines[] = 'شكراً لثقتك بنا! ☕';

        return implode("\n", $lines);
    }

    protected function formatOrderItems(Order $order, string $symbol): string
    {
        return $order->items
            ->map(function (OrderItem $item) use ($symbol): string {
                $label = $item->displayLabel();
                $line = sprintf(
                    '• %s × %d — %s %s',
                    $label,
                    (int) $item->quantity,
                    number_format((float) $item->line_total, 2, '.', ''),
                    $symbol,
                );

                $addons = $item->addons
                    ->map(fn ($addon): string => '   ↳ '.$addon->addon_name.($addon->quantity > 1 ? ' ×'.$addon->quantity : ''))
                    ->implode("\n");

                return $addons !== '' ? $line."\n".$addons : $line;
            })
            ->implode("\n");
    }

    protected function formatTotals(Order $order, string $symbol): string
    {
        $total = (float) $order->total;
        $deliveryFee = (float) ($order->delivery_fee ?? 0);
        $subtotal = max(0, round($total - $deliveryFee, 2));

        $lines = [
            '💰 *الإجمالي:* '.number_format($total, 2, '.', '')." {$symbol}",
        ];

        if ($deliveryFee > 0) {
            $lines[] = '   (أطباق: '.number_format($subtotal, 2, '.', '')." {$symbol} + توصيل: "
                .number_format($deliveryFee, 2, '.', '')." {$symbol})";
        }

        return implode("\n", $lines);
    }

    protected function storeWhatsAppPhone(): ?string
    {
        $phone = preg_replace('/\D+/', '', $this->settings->current()->whatsapp_phone ?? '') ?? '';

        return $phone !== '' ? $phone : null;
    }

    protected function formatDisplayPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        return '+'.$digits;
    }
}
