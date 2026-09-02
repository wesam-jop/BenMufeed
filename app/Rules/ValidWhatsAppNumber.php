<?php

namespace App\Rules;

use App\Services\Shared\PhoneNumberService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidWhatsAppNumber implements ValidationRule
{
    public function __construct(
        protected string $defaultRegion = PhoneNumberService::DEFAULT_REGION,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phones = app(PhoneNumberService::class);

        if (! is_string($value) || ! $phones->isValidMobile($value, $this->defaultRegion)) {
            $fail(__('auth.invalid_whatsapp'));
        }
    }
}
