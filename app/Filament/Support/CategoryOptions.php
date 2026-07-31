<?php

namespace App\Filament\Support;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryOptions
{
    /**
     * @return array<int|string, string>
     */
    public static function idNameMap(): array
    {
        return Cache::remember('filament.category_options', 600, fn (): array => Category::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all());
    }

    public static function forgetCache(): void
    {
        Cache::forget('filament.category_options');
    }
}
