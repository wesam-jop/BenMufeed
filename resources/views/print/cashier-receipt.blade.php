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
        <div class="bold" style="font-size: 15px;">فاتورة — {{ $serviceLabel }}</div>
        <div style="font-size: 11px;">
            #{{ $order->id }}
            @if ($order->closed_at)
                — {{ $order->closed_at->format('Y-m-d H:i') }}
            @else
                — {{ $order->created_at->format('Y-m-d H:i') }}
            @endif
        </div>
    </div>

    @if ($order->customer_name)
        <div class="divider"></div>
        <div><span class="bold">العميل:</span> {{ $order->customer_name }}</div>
        @if ($order->customer_phone)
            <div><span class="bold">الهاتف:</span> {{ $order->customer_phone }}</div>
        @endif
    @endif

    @if ($order->delivery_location_note)
        <div><span class="bold">الموقع:</span> {{ $order->delivery_location_note }}</div>
    @endif

    @if ($order->customer_note)
        <div><span class="bold">ملاحظة:</span> {{ $order->customer_note }}</div>
    @endif

    <div class="divider"></div>

    <table>
        @foreach ($items as $item)
            <tr>
                <td class="qty">×{{ $item->quantity }}</td>
                <td>{{ $item->displayLabel() }}</td>
                <td class="price">{{ number_format((float) $item->line_total, 2) }} {{ $currency }}</td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="bold">الإجمالي</td>
            <td class="price bold">{{ number_format((float) $order->total, 2) }} {{ $currency }}</td>
        </tr>
        <tr>
            <td class="bold">المقبوض</td>
            <td class="price bold">{{ number_format($paidAmount, 2) }} {{ $currency }}</td>
        </tr>
        @if ($order->table_number && $paidAmount < (float) $order->total)
            <tr>
                <td>الباقي</td>
                <td class="price">{{ number_format((float) $order->total - $paidAmount, 2) }} {{ $currency }}</td>
            </tr>
        @endif
    </table>

    @if ($order->cashier)
        <div class="divider"></div>
        <div class="center" style="font-size: 11px;">الكاشير: {{ $order->cashier->name }}</div>
    @endif

    @if ($footer)
        <div class="divider"></div>
        <div class="center" style="font-size: 11px;">{{ $footer }}</div>
    @endif

    @if ($cashierPrinterName)
        <div class="center" style="font-size: 9px; margin-top: 8px; opacity: 0.5;">{{ $cashierPrinterName }}</div>
    @endif
@endsection
