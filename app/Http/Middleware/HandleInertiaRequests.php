<?php

namespace App\Http\Middleware;

use App\Services\StorefrontCustomerAddressService;
use App\Services\StorefrontCustomerAuthService;
use App\Support\StorefrontCache;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{

    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'site' => StorefrontCache::sharedSite(),
            'phoneCountries' => StorefrontCache::phoneCountries(),
            'deliveryFee' => StorefrontCache::deliveryFee(),
            'tableScanPolicy' => [
                'idle_minutes' => (int) config('store.table_scan_idle_minutes', 45),
                'max_hours' => (int) config('store.table_scan_max_hours', 3),
            ],
            'storefrontCustomer' => fn () => $this->storefrontCustomerPayload($request),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function storefrontCustomerPayload(Request $request): ?array
    {
        $customer = app(StorefrontCustomerAuthService::class)->resolve($request);

        if ($customer === null) {
            return null;
        }

        $payload = $customer->toStorefrontArray();

        if ($customer->isVerified()) {
            $addresses = app(StorefrontCustomerAddressService::class)->listFor($customer);
            $payload['addresses'] = $addresses->map->toStorefrontArray()->values()->all();
        } else {
            $payload['addresses'] = [];
        }

        return $payload;
    }
}
