<?php

if (! function_exists('flash_toast')) {
    /**
     * @param  'success'|'info'|'warning'|'error'  $type
     * @param  array<string, string|int|float>  $replace
     * @return array{type: string, message: string}
     */
    function flash_toast(string $type, string $key, array $replace = []): array
    {
        return [
            'type' => $type,
            'message' => __($key, $replace),
        ];
    }
}

if (! function_exists('toast_success')) {
    /** @param  array<string, string|int|float>  $replace */
    function toast_success(string $key, array $replace = []): array
    {
        return flash_toast('success', $key, $replace);
    }
}

if (! function_exists('toast_error')) {
    /** @param  array<string, string|int|float>  $replace */
    function toast_error(string $key, array $replace = []): array
    {
        return flash_toast('error', $key, $replace);
    }
}

if (! function_exists('toast_warning')) {
    /** @param  array<string, string|int|float>  $replace */
    function toast_warning(string $key, array $replace = []): array
    {
        return flash_toast('warning', $key, $replace);
    }
}

if (! function_exists('toast_info')) {
    /** @param  array<string, string|int|float>  $replace */
    function toast_info(string $key, array $replace = []): array
    {
        return flash_toast('info', $key, $replace);
    }
}
