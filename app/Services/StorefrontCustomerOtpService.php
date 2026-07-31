<?php

namespace App\Services;

use App\Enums\StorefrontCustomerAccountType;
use App\Enums\StorefrontCustomerCreatedVia;
use App\Models\Order;
use App\Models\StorefrontCustomer;
use App\Models\StorefrontCustomerOtp;
use App\Support\PhoneCountries;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StorefrontCustomerOtpService
{
    public function __construct(
        protected WhatsAppService $whatsapp,
        protected StorefrontCustomerAuthService $auth,
    ) {}

    public function send(string $phone, ?string $name = null): void
    {
        $normalizedPhone = $this->normalizePhone($phone);

        $latest = StorefrontCustomerOtp::query()
            ->where('phone', $normalizedPhone)
            ->orderByDesc('id')
            ->first();

        if ($latest !== null) {
            $resendAfter = (int) config('store.storefront_customer_otp_resend_seconds', 60);
            $secondsSince = $latest->sent_at->diffInSeconds(now());

            if ($secondsSince < $resendAfter) {
                throw ValidationException::withMessages([
                    'otp' => 'انتظر '.($resendAfter - $secondsSince).' ثانية قبل إعادة إرسال الرمز.',
                ]);
            }
        }

        $code = (string) random_int(100000, 999999);
        $ttlMinutes = (int) config('store.storefront_customer_otp_ttl_minutes', 10);

        StorefrontCustomerOtp::query()->create([
            'phone' => $normalizedPhone,
            'code_hash' => hash('sha256', $code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes($ttlMinutes),
            'sent_at' => now(),
        ]);

        $greeting = filled($name) ? "مرحباً {$name}" : 'مرحباً';
        $message = "{$greeting}،\nرمز التحقق من بن مفيد: {$code}\nصالح لمدة {$ttlMinutes} دقائق.";

        $this->whatsapp->sendText($normalizedPhone, $message, 'storefront_otp');
    }

    /**
     * @return array{customer: StorefrontCustomer, token: string}
     */
    public function verify(
        string $phone,
        string $code,
        string $name,
        StorefrontCustomerCreatedVia $createdVia,
        ?StorefrontCustomer $currentCustomer = null,
    ): array {
        $normalizedPhone = $this->normalizePhone($phone);
        $trimmedName = trim($name);

        if ($trimmedName === '') {
            throw ValidationException::withMessages([
                'customer_name' => 'يرجى إدخال الاسم الكامل.',
            ]);
        }

        $otp = StorefrontCustomerOtp::query()
            ->where('phone', $normalizedPhone)
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if ($otp === null) {
            throw ValidationException::withMessages([
                'otp' => 'انتهت صلاحية الرمز — اطلب رمزاً جديداً.',
            ]);
        }

        $maxAttempts = (int) config('store.storefront_customer_otp_max_attempts', 3);

        if ($otp->attempts >= $maxAttempts) {
            throw ValidationException::withMessages([
                'otp' => 'تجاوزت عدد المحاولات — اطلب رمزاً جديداً.',
            ]);
        }

        if (! hash_equals($otp->code_hash, hash('sha256', trim($code)))) {
            $otp->increment('attempts');

            throw ValidationException::withMessages([
                'otp' => 'رمز التحقق غير صحيح.',
            ]);
        }

        return DB::transaction(function () use ($normalizedPhone, $trimmedName, $createdVia, $currentCustomer): array {
            $existingByPhone = StorefrontCustomer::query()
                ->where('phone', $normalizedPhone)
                ->where('is_active', true)
                ->first();

            if ($existingByPhone !== null && $existingByPhone->isVerified()) {
                if (
                    $currentCustomer !== null
                    && $currentCustomer->isTableGuest()
                    && $currentCustomer->getKey() !== $existingByPhone->getKey()
                ) {
                    $this->mergeTableGuestInto($currentCustomer, $existingByPhone);
                }

                $customer = $existingByPhone->fresh('defaultTable');

                return [
                    'customer' => $customer,
                    'token' => $this->auth->issueSession($customer),
                ];
            }

            if ($currentCustomer !== null && $currentCustomer->is_active) {
                $customer = $this->upgradeCustomer($currentCustomer, $trimmedName, $normalizedPhone, $createdVia);

                return [
                    'customer' => $customer,
                    'token' => $this->auth->issueSession($customer),
                ];
            }

            if ($existingByPhone !== null) {
                $customer = $this->upgradeCustomer($existingByPhone, $trimmedName, $normalizedPhone, $createdVia);

                return [
                    'customer' => $customer,
                    'token' => $this->auth->issueSession($customer),
                ];
            }

            $customer = StorefrontCustomer::query()->create([
                'name' => $trimmedName,
                'phone' => $normalizedPhone,
                'phone_verified_at' => now(),
                'account_type' => StorefrontCustomerAccountType::Verified,
                'created_via' => $createdVia,
                'is_active' => true,
            ]);

            return [
                'customer' => $customer->load('defaultTable'),
                'token' => $this->auth->issueSession($customer),
            ];
        });
    }

    protected function upgradeCustomer(
        StorefrontCustomer $customer,
        string $name,
        string $phone,
        StorefrontCustomerCreatedVia $createdVia,
    ): StorefrontCustomer {
        $customer->forceFill([
            'name' => $name,
            'phone' => $phone,
            'phone_verified_at' => now(),
            'account_type' => StorefrontCustomerAccountType::Verified,
            'created_via' => $customer->created_via ?? $createdVia,
        ])->save();

        return $customer->fresh('defaultTable');
    }

    protected function mergeTableGuestInto(StorefrontCustomer $tableGuest, StorefrontCustomer $target): void
    {
        Order::query()
            ->where('storefront_customer_id', $tableGuest->getKey())
            ->update(['storefront_customer_id' => $target->getKey()]);

        $tableGuest->forceFill(['is_active' => false])->save();
    }

    protected function normalizePhone(string $phone): string
    {
        $parsed = PhoneCountries::parse($phone);
        $formatted = PhoneCountries::format($parsed['dial'], $parsed['iso'], $parsed['local']);

        if (! PhoneCountries::isValidFormatted($formatted)) {
            throw ValidationException::withMessages([
                'customer_phone_local' => 'يرجى إدخال رقم جوال صالح.',
            ]);
        }

        return $formatted;
    }
}
