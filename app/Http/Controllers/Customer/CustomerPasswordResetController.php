<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Rules\ValidWhatsAppNumber;
use App\Services\Shared\PhoneNumberService;
use App\Services\Storefront\StorefrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CustomerPasswordResetController extends Controller
{
    public function __construct(
        protected StorefrontService $storefront,
        protected PhoneNumberService $phones,
    ) {}

    public function showForgot(Request $request): Response
    {
        return Inertia::render('customer/password/forgot', [
            ...$this->pageProps(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'whatsapp' => ['required', 'string', new ValidWhatsAppNumber],
        ]);

        $e164 = $this->phones->normalize($request->input('whatsapp'));
        $variants = $this->phones->lookupVariants($request->input('whatsapp'));

        $customer = Customer::query()
            ->where('is_active', true)
            ->where(function ($query) use ($variants) {
                $query->whereIn('whatsapp', $variants)
                    ->orWhereIn('phone', $variants);
            })
            ->first();

        if ($customer !== null && $e164 !== null) {
            $token = Str::random(64);

            Cache::put(
                $this->cacheKey($token),
                [
                    'customer_id' => $customer->id,
                    'whatsapp' => $e164,
                ],
                now()->addMinutes(60),
            );

            $resetUrl = url('/customer/password/reset/'.$token);

            // WhatsApp delivery can be plugged here later; log for ops/local.
            Log::info('Customer password reset link', [
                'customer_id' => $customer->id,
                'whatsapp' => $e164,
                'url' => $resetUrl,
            ]);

            if (app()->environment(['local', 'testing'])) {
                return redirect()
                    ->route('customer.password.reset', ['token' => $token])
                    ->with('status', __('auth.reset_link_ready'));
            }
        }

        return back()->with('status', __('auth.reset_link_sent'));
    }

    public function showReset(string $token): Response|RedirectResponse
    {
        $payload = Cache::get($this->cacheKey($token));

        if (! is_array($payload) || empty($payload['customer_id'])) {
            return redirect()
                ->route('customer.password.forgot')
                ->with('error', __('auth.reset_token_invalid'));
        }

        return Inertia::render('customer/password/reset', [
            ...$this->pageProps(),
            'token' => $token,
            'whatsapp' => $payload['whatsapp'] ?? null,
        ]);
    }

    public function reset(Request $request, string $token): RedirectResponse
    {
        $payload = Cache::get($this->cacheKey($token));

        if (! is_array($payload) || empty($payload['customer_id'])) {
            return redirect()
                ->route('customer.password.forgot')
                ->with('error', __('auth.reset_token_invalid'));
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $customer = Customer::query()->find($payload['customer_id']);

        if ($customer === null || ! $customer->is_active) {
            Cache::forget($this->cacheKey($token));

            return redirect()
                ->route('customer.password.forgot')
                ->with('error', __('auth.reset_token_invalid'));
        }

        $customer->forceFill([
            'password' => $validated['password'],
            'whatsapp' => $payload['whatsapp'] ?? $customer->whatsapp,
        ])->save();

        Cache::forget($this->cacheKey($token));

        return redirect()
            ->route('auth.login')
            ->with('status', __('auth.password_updated'));
    }

    protected function cacheKey(string $token): string
    {
        return 'customer.password.reset.'.hash('sha256', $token);
    }

    /** @return array<string, mixed> */
    protected function pageProps(): array
    {
        $branch = $this->storefront->defaultBranch();
        $branchId = $branch?->id;

        return [
            'business' => $this->storefront->businessInfo($branch),
            'navCategories' => Cache::remember(
                'storefront.nav_categories.'.($branchId ?? 0),
                120,
                fn () => $this->storefront->categoriesQuery($branchId)
                    ->limit(8)
                    ->get(['id', 'name', 'slug', 'image']),
            ),
        ];
    }
}
