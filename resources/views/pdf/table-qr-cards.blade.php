<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 12px; }
        .grid { width: 100%; }
        .card {
            display: inline-block;
            width: 48%;
            margin: 1%;
            vertical-align: top;
            border: 2px solid #3c281e;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            page-break-inside: avoid;
        }
        .site { font-size: 14px; color: #666; margin-bottom: 8px; }
        .title { font-size: 22px; font-weight: bold; color: #3c281e; margin: 8px 0; }
        .num { font-size: 36px; font-weight: bold; color: #c8a27a; }
        img { width: 180px; height: 180px; margin: 12px auto; display: block; }
        .hint { font-size: 11px; color: #444; margin-top: 8px; line-height: 1.5; }
        .url { font-size: 8px; color: #999; word-break: break-all; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="grid">
        @foreach ($cards as $card)
            <div class="card">
                <div class="site">{{ $siteName }}</div>
                <div class="title">{{ $card['label'] }}</div>
                <div class="num">#{{ $card['number'] }}</div>
                <img src="{{ $card['qr_data_uri'] }}" alt="QR">
                <!-- <div class="hint">امسح الرمز لطلبك من الجوال</div>
                <div class="url">{{ $card['url'] }}</div> -->
            </div>
        @endforeach
    </div>
</body>
</html>
