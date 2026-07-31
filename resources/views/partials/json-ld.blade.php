@php
    /** @var list<array<string, mixed>>|null $structuredData */
    $structuredData = $structuredData ?? [];
@endphp

@foreach ($structuredData as $block)
<script type="application/ld+json">{!! json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endforeach
