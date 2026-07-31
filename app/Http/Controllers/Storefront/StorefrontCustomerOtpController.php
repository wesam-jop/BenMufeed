<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\PosServiceType;
use App\Enums\StorefrontCustomerCreatedVia;
use App\Http\Controllers\Controller;
use App\Services\StorefrontCustomerAuthService;
use App\Services\StorefrontCustomerOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StorefrontCustomerOtpController extends Controller
{
    public function send(Request $request, StorefrontCustomerOtpService $otp): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'customer_phone_country' => ['nullable', 'string', 'max:16'],
            'customer_phone_local' => ['nullable', 'string', 'max:16'],
        ]);

        $phone = $this->resolvePhone($request->all());

        $otp->send($phone, $validated['customer_name'] ?? null);

        return response()->json([
            'message' => 'تم إرسال رمز التحقق إلى واتساب.',
        ]);
    }

    public function verify(
        Request $request,
        StorefrontCustomerOtpService $otp,
        StorefrontCustomerAuthService $auth,
    ): JsonResponse {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'customer_phone_country' => ['nullable', 'string', 'max:16'],
            'customer_phone_local' => ['nullable', 'string', 'max:16'],
            'otp' => ['required', 'string', 'size:6'],
            'service_type' => ['required', Rule::enum(PosServiceType::class)],
        ]);

        $phone = $this->resolvePhone($request->all());
        $createdVia = $validated['service_type'] === PosServiceType::Delivery->value
            ? StorefrontCustomerCreatedVia::DeliveryOtp
            : StorefrontCustomerCreatedVia::TakeawayOtp;

        $current = $auth->resolve($request);

        $result = $otp->verify(
            $phone,
            $validated['otp'],
            $validated['customer_name'],
            $createdVia,
            $current,
        );

        return response()
            ->json([
                'message' => 'تم التحقق بنجاح.',
                'customer' => $result['customer']->toStorefrontArray(),
            ])
            ->withCookie($auth->makeSessionCookie($result['token']));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function resolvePhone(array $input): string
    {
        $country = (string) ($input['customer_phone_country'] ?? '');
        $local = (string) ($input['customer_phone_local'] ?? '');

        if ($country !== '' && $local !== '') {
            [$dial, $iso] = \App\Support\PhoneCountries::splitOptionKey($country);

            return \App\Support\PhoneCountries::format($dial, $iso, $local);
        }

        $existing = (string) ($input['customer_phone'] ?? '');

        if ($existing === '') {
            throw ValidationException::withMessages([
                'customer_phone' => 'يرجى إدخال رقم الهاتف.',
            ]);
        }

        $parsed = \App\Support\PhoneCountries::parse($existing);

        return \App\Support\PhoneCountries::format($parsed['dial'], $parsed['iso'], $parsed['local']);
    }
}
