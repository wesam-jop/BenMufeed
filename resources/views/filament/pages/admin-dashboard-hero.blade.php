<div class="bm-dash-hero">
    <div class="bm-dash-hero__inner">
        <div>
            <p class="bm-dash-hero__greeting">مرحباً، {{ $userName }}</p>
            <h2 class="bm-dash-hero__title">{{ $brandName }}</h2>
            <p class="bm-dash-hero__subtitle">لوحة إدارة المطعم — طلبات، مخزون، وتقارير في مكان واحد.</p>
        </div>
        <span class="bm-dash-hero__badge inline-flex items-center gap-1.5">
            <x-ui.icon name="cup" class="h-4 w-4" />
            بن مفيد
        </span>
    </div>
</div>

<div class="bm-dash-links">
    @foreach ($quickLinks as $link)
        <a href="{{ $link['url'] }}" class="bm-dash-link bm-dash-link--{{ $link['color'] ?? 'gray' }}">
            <span class="bm-dash-link__icon">
                <x-filament::icon :icon="$link['icon']" class="bm-dash-link__svg" />
            </span>
            <span class="bm-dash-link__text">
                <span class="bm-dash-link__label">{{ $link['label'] }}</span>
                <span class="bm-dash-link__desc">{{ $link['description'] }}</span>
            </span>
        </a>
    @endforeach
</div>
