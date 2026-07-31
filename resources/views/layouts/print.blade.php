<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'طباعة' }}</title>
    <style>
        @page {
            size: {{ $paperWidthMm ?? 80 }}mm auto;
            margin: 2mm;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.35;
            color: #000;
            margin: 0;
            padding: 4mm;
            width: {{ $paperWidthMm ?? 80 }}mm;
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .logo { max-width: 48mm; max-height: 18mm; margin: 0 auto 6px; display: block; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .qty { width: 12mm; text-align: center; font-weight: bold; }
        .price { text-align: left; white-space: nowrap; }
        .append-badge {
            display: inline-block;
            border: 2px solid #000;
            padding: 2px 8px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        @media screen {
            body {
                border: 1px solid #ccc;
                margin: 12px auto;
                background: #fff;
            }
        }
    </style>
</head>
<body @if (empty($silent)) onload="window.print()" @endif>
    @yield('content')
</body>
</html>
