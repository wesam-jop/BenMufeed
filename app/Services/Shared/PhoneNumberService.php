<?php

namespace App\Services\Shared;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberService
{
    public const DEFAULT_REGION = 'SY';

    protected PhoneNumberUtil $util;

    public function __construct()
    {
        $this->util = PhoneNumberUtil::getInstance();
    }

    /**
     * Normalize to E.164 (e.g. +963912345678). Returns null if invalid.
     */
    public function normalize(?string $input, string $defaultRegion = self::DEFAULT_REGION): ?string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        try {
            $number = $this->util->parse($input, strtoupper($defaultRegion));
        } catch (NumberParseException) {
            return null;
        }

        if (! $this->util->isValidNumber($number)) {
            return null;
        }

        return $this->util->format($number, PhoneNumberFormat::E164);
    }

    public function isValid(?string $input, string $defaultRegion = self::DEFAULT_REGION): bool
    {
        return $this->normalize($input, $defaultRegion) !== null;
    }

    /**
     * Prefer mobile-capable numbers for WhatsApp identity.
     */
    public function isValidMobile(?string $input, string $defaultRegion = self::DEFAULT_REGION): bool
    {
        $input = trim((string) $input);

        if ($input === '') {
            return false;
        }

        try {
            $number = $this->util->parse($input, strtoupper($defaultRegion));
        } catch (NumberParseException) {
            return false;
        }

        if (! $this->util->isValidNumber($number)) {
            return false;
        }

        $type = $this->util->getNumberType($number);

        return in_array($type, [
            PhoneNumberType::MOBILE,
            PhoneNumberType::FIXED_LINE_OR_MOBILE,
            PhoneNumberType::PERSONAL_NUMBER,
            PhoneNumberType::VOIP,
            PhoneNumberType::UNKNOWN,
        ], true);
    }

    /**
     * Variants for DB lookup across legacy formats.
     *
     * @return list<string>
     */
    public function lookupVariants(?string $input, string $defaultRegion = self::DEFAULT_REGION): array
    {
        $e164 = $this->normalize($input, $defaultRegion);
        $variants = [];

        if ($e164 !== null) {
            $variants[] = $e164;
            $digits = ltrim($e164, '+');
            $variants[] = $digits;

            try {
                $number = $this->util->parse($e164, null);
                $national = (string) $number->getNationalNumber();
                $variants[] = $national;
                $variants[] = '0'.$national;
            } catch (NumberParseException) {
                //
            }
        }

        $raw = preg_replace('/\D+/', '', (string) $input) ?: '';
        if ($raw !== '') {
            $variants[] = $raw;
            $variants[] = '+'.$raw;
        }

        return array_values(array_unique(array_filter($variants)));
    }
}
