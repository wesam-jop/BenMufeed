@php($failedPrintCount = $failedPrintCount ?? 0)
<header class="pos-header">
    <div class="pos-header-start">
        <a href="{{ route('pos.cashier') }}" class="pos-brand pos-brand-link">
            <span class="pos-brand-icon" aria-hidden="true">
                <x-pos.icon name="cup" class="pos-icon pos-icon-brand" />
            </span>
            <div>
                <p class="pos-brand-title">بن مفيد</p>
                <p class="pos-brand-sub">كاشير</p>
            </div>
        </a>
    </div>
    <div class="pos-header-center">
        @isset($headerCenter)
            {{ $headerCenter }}
        @endisset
    </div>
    <div class="pos-header-end">
        <a href="{{ route('pos.website-orders') }}" @class(['pos-nav-link', 'pos-nav-link-website', 'pos-nav-link-active' => request()->routeIs('pos.website-orders')])>
            طلبات اونلاين
            @if (($pendingWebsiteCount ?? 0) > 0)
                <span class="pos-cart-badge">{{ $pendingWebsiteCount }}</span>
            @endif
        </a>
        <a href="{{ route('pos.cashier') }}" @class(['pos-nav-link', 'pos-nav-link-active' => request()->routeIs('pos.cashier')])>الكاشير</a>
        <a href="{{ route('pos.invoices') }}" @class(['pos-nav-link', 'pos-nav-link-active' => request()->routeIs('pos.invoices*')])>سجل الفواتير</a>
        <a
            href="{{ route('pos.print-log') }}"
            data-pos-print-log-link
            @class(['pos-nav-link', 'pos-nav-link-active' => request()->routeIs('pos.print-log')])
        >
            <span class="relative inline-flex">
                سجل الطباعة
                @if ($failedPrintCount > 0)
                    <span data-print-fail-badge class="absolute -end-3 -top-2 flex min-h-[1rem] min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-0.5 text-[9px] font-bold text-white">
                        {{ $failedPrintCount > 99 ? '99+' : $failedPrintCount }}
                    </span>
                @endif
            </span>
        </a>
        <a href="{{ route('pos.tables') }}" @class(['pos-nav-link', 'pos-nav-link-active' => request()->routeIs('pos.tables')])>
            الطاولات
            @if (($openTablesCount ?? 0) > 0)
                <span class="pos-cart-badge">{{ $openTablesCount }}</span>
            @endif
        </a>
        <a href="{{ route('pos.printing') }}" @class(['pos-nav-link', 'pos-nav-link-active' => request()->routeIs('pos.printing')])>ربط الطابعات</a>
        <a href="{{ url('/admin') }}" class="pos-link-admin">لوحة التحكم</a>
        <span class="pos-cashier">{{ auth()->user()->name }}</span>
    </div>
</header>
