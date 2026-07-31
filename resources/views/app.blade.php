<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @if (! empty($siteLogoUrl))
            <link rel="icon" href="{{ $siteLogoUrl }}">
            <link rel="apple-touch-icon" href="{{ $siteLogoUrl }}">
        @endif

        @include('partials.social-meta')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=el-messiri:400,500,600,700|tajawal:300,400,500,700&display=swap" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite('resources/js/app.jsx')
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-[#f5efe6] text-store-primary">
        @inertia
    </body>
</html>
