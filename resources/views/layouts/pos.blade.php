<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'كاشير — بن مفيد' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=el-messiri:400,500,600,700|tajawal:300,400,500,700&display=swap" rel="stylesheet">
    @php($printSettings = \App\Models\PosSetting::current())
    <meta name="pos-kitchen-printer" content="{{ $printSettings->kitchen_printer_name }}">
    <meta name="pos-cashier-printer" content="{{ $printSettings->cashier_printer_name }}">
    <meta name="pos-paper-width-mm" content="{{ $printSettings->receipt_paper_width_mm }}">
    @vite('resources/js/pos.js')
    @livewireStyles
</head>
<body class="pos-body">
    <livewire:pos.pos-order-alerts />
    <livewire:pos.pos-website-order-alerts />
    {{ $slot }}
    @livewireScripts
</body>
</html>
