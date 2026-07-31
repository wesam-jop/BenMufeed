<?php

namespace App\Services;

use App\Models\RestaurantTable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class RestaurantTableService
{
    /**
     * @return Collection<int, RestaurantTable>
     */
    public function activeTables(): Collection
    {
        $ids = Cache::remember('restaurant_tables.active_ids', 600, fn (): array => RestaurantTable::query()
            ->where('is_active', true)
            ->orderBy('number')
            ->pluck('id')
            ->all());

        if ($ids === []) {
            return new Collection;
        }

        return RestaurantTable::query()
            ->whereIn('id', $ids)
            ->orderBy('number')
            ->get();
    }

    public static function forgetCache(): void
    {
        Cache::forget('restaurant_tables.active');
        Cache::forget('restaurant_tables.active_ids');
    }

    public function isActiveTableNumber(int $number): bool
    {
        return RestaurantTable::query()
            ->where('number', $number)
            ->where('is_active', true)
            ->exists();
    }

    public function normalizeToken(string $token): string
    {
        $token = trim(urldecode($token));

        if (($queryPos = strpos($token, '?')) !== false) {
            $token = substr($token, 0, $queryPos);
        }

        if (($hashPos = strpos($token, '#')) !== false) {
            $token = substr($token, 0, $hashPos);
        }

        return strtolower(rtrim($token, '/'));
    }

    public function findByToken(string $token): ?RestaurantTable
    {
        $token = $this->normalizeToken($token);

        if ($token === '') {
            return null;
        }

        return RestaurantTable::query()
            ->where('qr_token', $token)
            ->where('is_active', true)
            ->first();
    }

    public function resolveToken(string $token): RestaurantTable
    {
        $table = $this->findByToken($token);

        if ($table === null) {
            throw ValidationException::withMessages([
                'table_token' => 'رمز الطاولة غير صالح أو الطاولة غير مفعّلة.',
            ]);
        }

        return $table;
    }

    public function scanUrl(RestaurantTable $table): string
    {
        return URL::to('/t/'.$table->qr_token);
    }

    public function qrImageUrl(RestaurantTable $table, int $size = 320): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size='.$size.'x'.$size.'&data='.urlencode($this->scanUrl($table));
    }

    /**
     * الرابط الموحّد للباركود الواحد لكل الطاولات.
     */
    public function venueScanUrl(): string
    {
        $token = \App\Models\SiteSetting::current()->venueScanToken();

        return URL::to('/t/'.$token);
    }

    public function venueQrImageUrl(int $size = 360): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size='.$size.'x'.$size.'&data='.urlencode($this->venueScanUrl());
    }

    public function regenerateToken(RestaurantTable $table): RestaurantTable
    {
        $table->forceFill([
            'qr_token' => RestaurantTable::generateToken(),
        ])->save();

        return $table->fresh();
    }
}
