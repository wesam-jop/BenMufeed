@props([
    'name',
    'class' => 'pos-icon',
])

<x-ui.icon :name="$name" {{ $attributes->merge(['class' => $class]) }} />
