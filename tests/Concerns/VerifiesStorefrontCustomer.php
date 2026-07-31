<?php

namespace Tests\Concerns;

use App\Models\StorefrontCustomerOtp;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

trait VerifiesStorefrontCustomer
{
    /**
     * @return array{0: string, 1: string}
     */
    protected function verifiedCustomerCookie(
        string $name = 'أحمد علي',
        string $phoneLocal = '912345678',
        string $serviceType = 'takeaway',
    ): array {
        Config::set('services.mirsalli.user', 'test-user');
        Http::fake();

        StorefrontCustomerOtp::query()->create([
            'phone' => '963'.ltrim($phoneLocal, '0'),
            'code_hash' => hash('sha256', '123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'sent_at' => now()->subMinute(),
        ]);

        $verify = $this
            ->withCredentials()
            ->postJson('/api/account/otp/verify', [
            'customer_name' => $name,
            'customer_phone_country' => '963|SY',
            'customer_phone_local' => $phoneLocal,
            'otp' => '123456',
            'service_type' => $serviceType,
        ]);

        $verify->assertOk();

        $cookieName = (string) config('store.storefront_customer_cookie', 'bm_sc');
        $cookie = collect($verify->headers->getCookies())
            ->first(fn ($item) => $item->getName() === $cookieName);

        if ($cookie === null) {
            throw new \RuntimeException('Storefront customer session cookie missing from OTP verify response.');
        }

        return [$cookieName, $cookie->getValue()];
    }
}
