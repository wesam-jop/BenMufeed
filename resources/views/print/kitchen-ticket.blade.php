@extends('layouts.print')

@section('content')
    <div class="center">
        @if ($showLogo && $logoUrl)
            <img src="{{ $logoUrl }}" alt="" class="logo">
        @endif
        <div class="bold" style="font-size: 14px;">{{ $businessName }}</div>
        @if ($header)
            <div>{{ $header }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <div class="center">
        @if ($isAppend)
            <div class="append-badge">إضافة — طبقة جديدة</div>
        @endif
        <div class="bold" style="font-size: 16px;">{{ $ticket->service_label }}</div>
        <div>{{ $kindLabel }}</div>
        <div style="font-size: 11px;">{{ $ticket->created_at->format('Y-m-d H:i') }} — #{{ $ticket->order_id }}</div>
    </div>

    @if ($order->customer_name || $order->customer_phone)
        <div class="divider"></div>
        @if ($order->customer_name)
            <div><span class="bold">العميل:</span> {{ $order->customer_name }}</div>
        @endif
        @if ($order->customer_phone)
            <div><span class="bold">هاتف:</span> {{ $order->customer_phone }}</div>
        @endif
        @if ($order->delivery_location_note)
            <div><span class="bold">الموقع:</span> {{ $order->delivery_location_note }}</div>
        @endif
    @endif

    @if ($order->customer_note)
        <div class="divider"></div>
        <div><span class="bold">ملاحظة:</span> {{ $order->customer_note }}</div>
    @endif

    <div class="divider"></div>

    <table>
        @foreach ($items as $item)
            <tr>
                <td class="qty">×{{ $item->quantity }}</td>
                <td class="bold">{{ $item->displayLabel() }}</td>
            </tr>
        @endforeach
    </table>

    @if ($kitchenFooter)
        <div class="divider"></div>
        <div class="center" style="font-size: 11px;">{{ $kitchenFooter }}</div>
    @endif

    @if ($kitchenPrinterName)
        <div class="center" style="font-size: 9px; margin-top: 8px; opacity: 0.5;">{{ $kitchenPrinterName }}</div>
    @endif
@endsection
