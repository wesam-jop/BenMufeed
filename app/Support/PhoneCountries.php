<?php

namespace App\Support;

class PhoneCountries
{
    public const DEFAULT_DIAL = '963';

    /**
     * @return list<array{dial: string, iso: string, name: string, flag?: string}>
     */
    public static function countries(): array
    {
        return config('phone_countries.countries', []);
    }

    public static function defaultDial(): string
    {
        return (string) config('phone_countries.default_dial', self::DEFAULT_DIAL);
    }

    /**
     * @return array<string, string> dial code => label
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::countries() as $country) {
            $dial = (string) $country['dial'];
            $key = self::optionKey($dial, (string) $country['iso']);
            $flag = trim((string) ($country['flag'] ?? ''));
            $prefix = $flag !== '' ? "{$flag} " : '';
            $options[$key] = sprintf('%s%s (+%s)', $prefix, $country['name'], $dial);
        }

        return $options;
    }

    public static function optionKey(string $dial, string $iso): string
    {
        return $dial.'|'.$iso;
    }

    /**
     * @return array{dial: string, iso: string, local: string}
     */
    public static function parse(?string $phone): array
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return [
                'dial' => self::defaultDial(),
                'iso' => self::defaultIso(),
                'local' => '',
            ];
        }

        foreach (self::dialCodesByLength() as $dial) {
            if (str_starts_with($digits, $dial)) {
                $iso = self::isoForDial($dial) ?? self::defaultIso();
                $local = substr($digits, strlen($dial));

                return [
                    'dial' => $dial,
                    'iso' => $iso,
                    'local' => self::normalizeLocal($local),
                ];
            }
        }

        return [
            'dial' => self::defaultDial(),
            'iso' => self::defaultIso(),
            'local' => self::normalizeLocal($digits),
        ];
    }

    public static function format(string $dial, string $iso, string $local): string
    {
        $dial = preg_replace('/\D+/', '', $dial) ?? '';
        $local = self::normalizeLocal($local);

        if ($dial === '' || $local === '') {
            return '';
        }

        return $dial.$local;
    }

    public static function preview(?string $dialIso, ?string $local): string
    {
        [$dial, $iso] = self::splitOptionKey((string) $dialIso);
        $full = self::format($dial, $iso, (string) $local);

        if ($full === '') {
            return 'أدخل رقم الجوال بدون الصفر في البداية (مثال: 9xxxxxxxx).';
        }

        return 'الرقم الكامل للواتساب: +'.$full;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function splitOptionKey(string $key): array
    {
        if (str_contains($key, '|')) {
            [$dial, $iso] = explode('|', $key, 2);

            return [$dial, $iso];
        }

        return [$key, self::isoForDial($key) ?? self::defaultIso()];
    }

    public static function defaultOptionKey(): string
    {
        $dial = self::defaultDial();
        $iso = self::defaultIso();

        return self::optionKey($dial, $iso);
    }

    /**
     * @return array{defaultKey: string, countries: list<array{key: string, dial: string, iso: string, name: string, flag: string, label: string}>}
     */
    public static function forFrontend(): array
    {
        $priority = array_flip((array) config('phone_countries.priority_dials', []));
        $countries = self::countries();

        usort($countries, function (array $a, array $b) use ($priority): int {
            $aDial = (string) $a['dial'];
            $bDial = (string) $b['dial'];
            $aRank = $priority[$aDial] ?? 9999;
            $bRank = $priority[$bDial] ?? 9999;

            if ($aRank !== $bRank) {
                return $aRank <=> $bRank;
            }

            return strcmp((string) $a['name'], (string) $b['name']);
        });

        $options = [];

        foreach ($countries as $country) {
            $dial = (string) $country['dial'];
            $iso = (string) $country['iso'];
            $key = self::optionKey($dial, $iso);
            $flag = trim((string) ($country['flag'] ?? ''));

            $options[] = [
                'key' => $key,
                'dial' => $dial,
                'iso' => $iso,
                'name' => (string) $country['name'],
                'flag' => $flag,
                'label' => self::options()[$key] ?? "+{$dial}",
            ];
        }

        return [
            'defaultKey' => self::defaultOptionKey(),
            'countries' => $options,
        ];
    }

    public static function isValidFormatted(?string $phone): bool
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        return strlen($digits) >= 10 && strlen($digits) <= 15;
    }

    public static function defaultIso(): string
    {
        foreach (self::countries() as $country) {
            if ((string) $country['dial'] === self::defaultDial()) {
                return (string) $country['iso'];
            }
        }

        return 'SY';
    }

    /**
     * @return list<string>
     */
    private static function dialCodesByLength(): array
    {
        static $codes = null;

        if ($codes !== null) {
            return $codes;
        }

        $codes = array_values(array_unique(array_map(
            fn (array $country): string => (string) $country['dial'],
            self::countries(),
        )));

        usort($codes, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $codes;
    }

    private static function isoForDial(string $dial): ?string
    {
        foreach (self::countries() as $country) {
            if ((string) $country['dial'] === $dial) {
                return (string) $country['iso'];
            }
        }

        return null;
    }

    private static function normalizeLocal(string $local): string
    {
        $local = preg_replace('/\D+/', '', $local) ?? '';

        return ltrim($local, '0');
    }
}
