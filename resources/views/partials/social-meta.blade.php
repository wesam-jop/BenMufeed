@php
    /** @var array<string, string|null>|null $seo */
    $seo = $seo ?? null;
@endphp

@if ($seo)
    <title>{{ $seo['title'] ?? config('app.name') }}</title>
    <meta name="description" content="{{ $seo['description'] ?? '' }}">
    @if (! empty($seo['robots']))
        <meta name="robots" content="{{ $seo['robots'] }}">
    @endif
    @if (! empty($seo['keywords']))
        <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif

    <meta property="og:locale" content="ar_SA">
    <meta property="og:site_name" content="{{ $seo['site_name'] ?? config('app.name') }}">
    <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
    <meta property="og:title" content="{{ $seo['title'] ?? '' }}">
    <meta property="og:description" content="{{ $seo['description'] ?? '' }}">
    @if (! empty($seo['url']))
        <meta property="og:url" content="{{ $seo['url'] }}">
        <link rel="canonical" href="{{ $seo['url'] }}">
    @endif
    @if (! empty($seo['prev_url']))
        <link rel="prev" href="{{ $seo['prev_url'] }}">
    @endif
    @if (! empty($seo['next_url']))
        <link rel="next" href="{{ $seo['next_url'] }}">
    @endif
    @if (! empty($seo['image']))
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta property="og:image:secure_url" content="{{ $seo['image'] }}">
        <meta property="og:image:alt" content="{{ $seo['title'] ?? '' }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] ?? '' }}">
    <meta name="twitter:description" content="{{ $seo['description'] ?? '' }}">
    @if (! empty($seo['image']))
        <meta name="twitter:image" content="{{ $seo['image'] }}">
        <meta name="twitter:image:alt" content="{{ $seo['title'] ?? '' }}">
    @endif
@endif
