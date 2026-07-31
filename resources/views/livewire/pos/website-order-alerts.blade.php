<div wire:poll.4s.visible="pollPending" class="pos-website-toasts">
    @foreach ($toasts as $toast)
        <div
            wire:key="website-toast-{{ $toast['id'] }}"
            @class([
                'pos-alert pos-alert-website',
                'pos-alert-website-delivery' => str_contains($toast['title'], 'توصيل'),
            ])
        >
            <div class="pos-alert-body">
                <p class="pos-alert-title">{{ $toast['title'] }}</p>
                <p class="pos-alert-sub">{{ $toast['sub'] }}</p>
                <a href="{{ $toast['url'] }}" class="pos-alert-link">عرض الطلب ←</a>
            </div>
            <button type="button" class="pos-alert-dismiss" wire:click="dismissToast({{ $toast['id'] }})" aria-label="إغلاق">×</button>
        </div>
    @endforeach
</div>
