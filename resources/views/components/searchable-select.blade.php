@props([
    'options' => [],
    'placeholder' => 'اختر...',
    'searchPlaceholder' => 'ابحث...',
    'clearable' => true,
    'required' => false,
])

@php
    $wireModel = $attributes->wire('model')->value();
    $optionsList = collect($options)->map(function ($option) {
        if (is_array($option)) {
            return [
                'value' => (string) ($option['value'] ?? ''),
                'label' => (string) ($option['label'] ?? ''),
                'search' => (string) ($option['search'] ?? $option['label'] ?? ''),
                'meta' => (string) ($option['meta'] ?? ''),
            ];
        }

        return [
            'value' => (string) $option->value,
            'label' => (string) $option->label,
            'search' => (string) ($option->search ?? $option->label),
            'meta' => (string) ($option->meta ?? ''),
        ];
    })->values()->all();
@endphp

<div
    x-data="searchableSelect({
        options: @js($optionsList),
        value: @entangle($wireModel).live,
        placeholder: @js($placeholder),
        searchPlaceholder: @js($searchPlaceholder),
        clearable: @js($clearable),
    })"
    x-on:keydown.escape.stop="close()"
    class="ss"
    {{ $attributes->except(['wire:model', 'wire:model.live', 'wire:model.blur', 'wire:model.defer', 'class']) }}
>
    <div x-ref="control" class="ss-control" x-on:click="openDropdown()" :class="{ 'ss-open': open }">
        <input
            type="text"
            class="ss-input {{ $attributes->get('class') }}"
            x-model="search"
            x-on:focus="openDropdown()"
            x-on:input="onSearchInput()"
            :placeholder="open ? searchPlaceholder : (selectedLabel || placeholder)"
            autocomplete="off"
            @if($required) required @endif
        >
        <button type="button" class="ss-toggle" x-on:click.stop="toggle()" tabindex="-1" aria-label="فتح القائمة">
            <svg viewBox="0 0 20 20" fill="currentColor" class="ss-chevron" :class="{ 'ss-chevron-up': open }">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
            </svg>
        </button>
        @if($clearable)
            <button
                type="button"
                class="ss-clear"
                x-show="value !== null && value !== ''"
                x-on:click.stop="clearSelection()"
                tabindex="-1"
                aria-label="مسح"
            >×</button>
        @endif
    </div>

    <template x-teleport="body">
        <div
            x-ref="dropdown"
            x-show="open"
            x-transition.opacity
            x-cloak
            class="ss-dropdown-portal"
            :style="dropdownStyle"
        >
            <template x-if="filteredOptions.length === 0">
                <p class="ss-empty">لا توجد نتائج.</p>
            </template>
            <ul class="ss-list">
                <template x-for="option in filteredOptions" :key="option.value">
                    <li>
                        <button
                            type="button"
                            class="ss-option"
                            :class="{ 'ss-option-selected': String(value) === String(option.value) }"
                            x-on:click="selectOption(option)"
                        >
                            <span class="ss-option-label" x-text="option.label"></span>
                            <span class="ss-option-meta" x-show="option.meta" x-text="option.meta"></span>
                        </button>
                    </li>
                </template>
            </ul>
            <p class="ss-list-hint" x-show="showSearchHint" x-text="searchHint"></p>
        </div>
    </template>
</div>
