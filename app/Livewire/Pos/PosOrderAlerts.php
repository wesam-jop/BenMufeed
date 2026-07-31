<?php

namespace App\Livewire\Pos;

use App\Models\PosSetting;
use App\Services\KitchenService;
use App\Support\DispatchesPrintJobs;
use Livewire\Component;

class PosOrderAlerts extends Component
{
    use DispatchesPrintJobs;

    public int $lastSeenTicketId = 0;

    /** @var array<int, array{id: int, label: string, kind: string, service: string, items_count: int}> */
    public array $alerts = [];

    public function mount(): void
    {
        $latest = \App\Models\KitchenTicket::query()->max('id');

        if (is_numeric($latest)) {
            $this->lastSeenTicketId = (int) $latest;
        }
    }

    public function pollAlerts(): void
    {
        $settings = PosSetting::current();
        $tickets = app(KitchenService::class)->ticketsSince($this->lastSeenTicketId);

        foreach ($tickets as $ticket) {
            $this->lastSeenTicketId = max($this->lastSeenTicketId, $ticket->getKey());

            $this->alerts[] = [
                'id' => $ticket->getKey(),
                'label' => $ticket->kind->isAppend() ? 'إضافة على طاولة' : 'طلب جديد',
                'kind' => $ticket->kind->value,
                'service' => $ticket->service_label,
                'items_count' => (int) ($ticket->order_items_count ?? 0),
            ];

            if ($settings->kitchen_alert_sound) {
                $this->dispatch('pos-play-alert', kind: $ticket->kind->isAppend() ? 'append' : 'new');
            }

            if ($settings->auto_print_kitchen && $ticket->printed_at === null) {
                $this->dispatchKitchenPrint($ticket->getKey());
            }
        }

        if (count($this->alerts) > 8) {
            $this->alerts = array_slice($this->alerts, -8);
        }
    }

    public function dismiss(int $ticketId): void
    {
        $this->alerts = array_values(array_filter(
            $this->alerts,
            fn (array $row): bool => $row['id'] !== $ticketId,
        ));
    }

    public function render()
    {
        return view('livewire.pos.order-alerts');
    }
}
