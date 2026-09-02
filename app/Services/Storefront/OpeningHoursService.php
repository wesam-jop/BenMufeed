<?php

namespace App\Services\Storefront;

use App\Models\Branch;
use App\Models\SiteSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class OpeningHoursService
{
    public function timezone(): string
    {
        $branch = Branch::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        $cached = Cache::get('business_settings');

        return $branch?->timezone
            ?? (is_array($cached) ? ($cached['timezone'] ?? null) : null)
            ?? config('app.timezone', 'Asia/Riyadh');
    }

    public function opensAt(): string
    {
        $value = SiteSetting::current()->store_opens_at;

        return $this->normalizeTime($value) ?? '10:00';
    }

    public function closesAt(): string
    {
        $value = SiteSetting::current()->store_closes_at;

        return $this->normalizeTime($value) ?? '21:00';
    }

    public function isOpenForDelivery(?Carbon $at = null): bool
    {
        $now = ($at ?? now())->timezone($this->timezone());
        $opens = $this->opensAt();
        $closes = $this->closesAt();

        $openTime = Carbon::parse($now->toDateString().' '.$opens, $this->timezone());
        $closeTime = Carbon::parse($now->toDateString().' '.$closes, $this->timezone());

        // Overnight window e.g. 22:00 → 02:00
        if ($closeTime->lessThanOrEqualTo($openTime)) {
            return $now->greaterThanOrEqualTo($openTime) || $now->lessThan($closeTime);
        }

        return $now->greaterThanOrEqualTo($openTime) && $now->lessThan($closeTime);
    }

    /**
     * @return array<string, mixed>
     */
    public function deliveryStatus(?Carbon $at = null): array
    {
        $open = $this->isOpenForDelivery($at);
        $opens = $this->opensAt();
        $closes = $this->closesAt();

        return [
            'is_open' => $open,
            'opens_at' => $opens,
            'closes_at' => $closes,
            'opens_at_label' => $this->formatLabel($opens),
            'closes_at_label' => $this->formatLabel($closes),
            'timezone' => $this->timezone(),
            'message' => $open
                ? 'التوصيل متاح الآن'
                : 'المقهى مغلق حالياً لطلبات التوصيل. أوقات العمل: '.$this->formatLabel($opens).' – '.$this->formatLabel($closes),
        ];
    }

    public function ensureOpenForDelivery(): void
    {
        if ($this->isOpenForDelivery()) {
            return;
        }

        $status = $this->deliveryStatus();

        throw ValidationException::withMessages([
            'delivery_hours' => $status['message'],
        ]);
    }

    private function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('H:i');
        }

        $string = (string) $value;

        if (preg_match('/^\d{2}:\d{2}/', $string, $matches)) {
            return substr($matches[0], 0, 5);
        }

        try {
            return Carbon::parse($string)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatLabel(string $time): string
    {
        try {
            return Carbon::createFromFormat('H:i', $time)
                ->locale('ar')
                ->translatedFormat('g:i A');
        } catch (\Throwable) {
            return $time;
        }
    }
}
