<div wire:poll.6s.visible="pollAlerts" class="pos-alerts-root">
    @foreach ($alerts as $alert)
        <div wire:key="alert-{{ $alert['id'] }}" @class(['pos-alert', 'pos-alert-append' => $alert['kind'] === 'table_append'])>
            <div class="pos-alert-body">
                <p class="pos-alert-title">{{ $alert['label'] }}</p>
                <p class="pos-alert-sub">{{ $alert['service'] }} — {{ $alert['items_count'] }} صنف</p>
            </div>
            <button type="button" class="pos-alert-dismiss" wire:click="dismiss({{ $alert['id'] }})" aria-label="إغلاق">×</button>
        </div>
    @endforeach
</div>
