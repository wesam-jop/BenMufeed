<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PosSetting;
use App\Models\SiteSetting;

class ReceiptPrintService
{
    public function __construct(
        protected PosSetting $settings,
        protected SiteSetting $site,
    ) {}

    public static function make(): self
    {
        return new self(PosSetting::current(), SiteSetting::current());
    }

    /**
     * @return array<string, mixed>
     */
    public function sharedVariables(): array
    {
        return [
            'businessName' => $this->settings->businessName(),
            'header' => $this->settings->receipt_header,
            'footer' => $this->settings->receipt_footer,
            'kitchenFooter' => $this->settings->kitchen_ticket_footer,
            'showLogo' => $this->settings->receipt_show_logo,
            'logoUrl' => $this->settings->receipt_show_logo ? $this->site->logoUrl() : null,
            'paperWidthMm' => max(58, min(80, (int) $this->settings->receipt_paper_width_mm)),
            'currency' => config('store.currency_symbol', '$'),
            'kitchenPrinterName' => $this->settings->kitchen_printer_name,
            'cashierPrinterName' => $this->settings->cashier_printer_name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function kitchenTicketData(\App\Models\KitchenTicket $ticket): array
    {
        $ticket->loadMissing(['orderItems', 'order']);

        return array_merge($this->sharedVariables(), [
            'ticket' => $ticket,
            'order' => $ticket->order,
            'items' => $ticket->orderItems,
            'isAppend' => $ticket->kind->isAppend(),
            'kindLabel' => $ticket->kind->label(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function cashierReceiptData(Order $order): array
    {
        $order->loadMissing(['items', 'cashier', 'restaurantTable']);

        return array_merge($this->sharedVariables(), [
            'order' => $order,
            'items' => $order->items,
            'paidAmount' => (float) ($order->paid_amount ?? $order->total),
            'serviceLabel' => match (true) {
                $order->table_number !== null => 'طاولة '.$order->table_number,
                $order->service_type === \App\Enums\PosServiceType::Delivery => 'توصيل',
                $order->service_type === \App\Enums\PosServiceType::Takeaway => 'استلام',
                default => $order->service_type?->label() ?? '—',
            },
        ]);
    }
}
