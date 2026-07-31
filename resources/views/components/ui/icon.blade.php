@props([
    'name',
    'class' => 'ui-icon',
])

@php
    $paths = \App\Support\Heroicons::path($name);
@endphp

@if ($paths)
    <svg {{ $attributes->merge(['class' => $class, 'xmlns' => 'http://www.w3.org/2000/svg', 'fill' => 'none', 'viewBox' => '0 0 24 24', 'stroke-width' => '1.5', 'stroke' => 'currentColor', 'aria-hidden' => 'true']) }}>
        @foreach ((array) $paths as $path)
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
        @endforeach
    </svg>
@endif
