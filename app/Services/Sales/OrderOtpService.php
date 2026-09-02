<?php

namespace App\Services\Sales;

use App\Models\OrderOtpVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderOtpService
{
    public function __construct(
        protected CartService $cart,
        protected TelegramService $telegram,
        protected GuestProfileService $guestProfile,
    ) {}

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '966')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '966'.substr($digits, 1);
        }

        return $digits;
    }

    /**
     * @return array{verification_id: int, debug_code?: string}
     */
    public function send(string $name, string $phone): array
    {
        if ($this->cart->summary()['count'] === 0) {
            throw ValidationException::withMessages([
                'cart' => ['السلة فارغة.'],
            ]);
        }

        $normalized = self::normalizePhone($phone);

        if (strlen($normalized) < 10) {
            throw ValidationException::withMessages([
                'whatsapp' => ['رقم الواتساب غير صالح.'],
            ]);
        }

        OrderOtpVerification::query()
            ->where('phone', $normalized)
            ->whereNull('verified_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        $verification = OrderOtpVerification::create([
            'phone' => $normalized,
            'name' => $name,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'session_id' => session()->getId(),
        ]);

        $this->dispatchOtp($normalized, $name, $code);

        $response = ['verification_id' => $verification->id];

        if (config('app.debug')) {
            $response['debug_code'] = $code;
        }

        return $response;
    }

    public function verify(int $verificationId, string $phone, string $code): OrderOtpVerification
    {
        $normalized = self::normalizePhone($phone);

        $verification = OrderOtpVerification::query()->find($verificationId);

        if ($verification === null || $verification->phone !== $normalized) {
            throw ValidationException::withMessages([
                'otp' => ['رمز التحقق غير صالح.'],
            ]);
        }

        if ($verification->isVerified()) {
            return $verification;
        }

        if ($verification->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => ['انتهت صلاحية رمز التحقق. أعد الإرسال.'],
            ]);
        }

        if ($verification->attempts >= 5) {
            throw ValidationException::withMessages([
                'otp' => ['تجاوزت عدد المحاولات المسموح. أعد إرسال الرمز.'],
            ]);
        }

        if (! Hash::check($code, $verification->code_hash)) {
            $verification->increment('attempts');

            throw ValidationException::withMessages([
                'otp' => ['رمز التحقق غير صحيح.'],
            ]);
        }

        $verification->update(['verified_at' => now()]);

        return $verification->fresh();
    }

    protected function dispatchOtp(string $phone, string $name, string $code): void
    {
        $displayPhone = $this->guestProfile->displayPhone($phone);
        $safeName = $this->telegram->escapeHtml($name);
        $safePhone = $this->telegram->escapeHtml($displayPhone);
        $safeCode = $this->telegram->escapeHtml($code);
        $appName = $this->telegram->escapeHtml(config('app.name', 'Kasher'));

        $message = implode("\n", [
            "<b>رمز تأكيد طلب {$appName}</b>",
            '',
            "<b>الاسم:</b> {$safeName}",
            "<b>الواتساب:</b> <code>{$safePhone}</code>",
            "<b>الرمز:</b> <code>{$safeCode}</code>",
            '',
            '<i>صالح لمدة 10 دقائق</i>',
        ]);

        if ($this->telegram->isConfigured()) {
            if (! $this->telegram->sendMessage($message)) {
                throw ValidationException::withMessages([
                    'whatsapp' => ['تعذّر إرسال رمز التحقق. حاول مرة أخرى لاحقاً.'],
                ]);
            }

            Log::info('OTP sent to Telegram', [
                'phone' => $phone,
                'name' => $name,
            ]);

            return;
        }

        Log::info('OTP generated (Telegram not configured)', [
            'phone' => $phone,
            'name' => $name,
            'code' => config('app.debug') ? $code : '[hidden]',
        ]);
    }
}
