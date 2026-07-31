<?php

namespace App\Services;

use App\Models\StorefrontCustomer;
use App\Models\StorefrontCustomerSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class StorefrontCustomerAuthService
{
    protected ?StorefrontCustomer $resolved = null;

    public function cookieName(): string
    {
        return (string) config('store.storefront_customer_cookie', 'bm_sc');
    }

    public function sessionDays(): int
    {
        return max(1, (int) config('store.storefront_customer_session_days', 30));
    }

    public function resolve(Request $request): ?StorefrontCustomer
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $token = (string) $request->cookie($this->cookieName(), '');

        if ($token === '') {
            return null;
        }

        $session = StorefrontCustomerSession::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->with('customer.defaultTable')
            ->first();

        if ($session === null) {
            return null;
        }

        $customer = $session->customer;

        if ($customer === null || ! $customer->is_active) {
            return null;
        }

        $session->forceFill(['last_used_at' => now()])->save();

        return $this->resolved = $customer;
    }

    public function issueSession(StorefrontCustomer $customer): string
    {
        $plainToken = Str::random(64);
        $expiresAt = now()->addDays($this->sessionDays());

        StorefrontCustomerSession::query()->create([
            'storefront_customer_id' => $customer->getKey(),
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt,
            'last_used_at' => now(),
        ]);

        return $plainToken;
    }

    public function makeSessionCookie(string $plainToken): SymfonyCookie
    {
        return Cookie::make(
            $this->cookieName(),
            $plainToken,
            $this->sessionDays() * 24 * 60,
            '/',
            null,
            request()->isSecure(),
            true,
            false,
            SymfonyCookie::SAMESITE_LAX,
        );
    }

    public function forgetSession(Request $request): void
    {
        $token = (string) $request->cookie($this->cookieName(), '');

        if ($token !== '') {
            StorefrontCustomerSession::query()
                ->where('token_hash', hash('sha256', $token))
                ->delete();
        }
    }

    public function forgetSessionCookie(): SymfonyCookie
    {
        return Cookie::forget($this->cookieName(), '/');
    }
}
