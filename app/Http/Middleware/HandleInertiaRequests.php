<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Customer;
use App\Services\Storefront\CartService;
use App\Services\Shared\CurrencyService;
use App\Services\Storefront\GuestProfileService;
use App\Services\Shared\PostLoginRedirectService;
use App\Services\Storefront\StorefrontService;
use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $locale = Locale::normalize(app()->getLocale());
        $isAdminArea = $request->is('admin/*', 'pos/*', 'kitchen/*');

        $user = Auth::guard('web')->user();
        $customer = Auth::guard('customer')->user();
        $permissions = $user
            ? $user->getAllPermissions()->pluck('name')->values()->all()
            : [];

        $userData = null;
        if ($user !== null) {
            $userData = $user->loadMissing('roles', 'branch')->toArray();
            $userData['permissions'] = $permissions;
        }

        $customerData = null;
        $favoriteProductIds = [];

        if ($customer instanceof Customer) {
            $customerData = [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'needs_password_setup' => ! $customer->hasPassword(),
            ];

            if (! $isAdminArea) {
                $favoriteProductIds = Cache::remember(
                    "customer.{$customer->id}.favorite_ids",
                    60,
                    fn () => $customer->favorites()->pluck('id')->values()->all(),
                );
            }
        }

        $currentBranch = null;
        if ($user?->branch_id) {
            $currentBranch = Cache::remember(
                "branch.mini.{$user->branch_id}",
                120,
                fn () => Branch::query()
                    ->whereKey($user->branch_id)
                    ->first(['id', 'name', 'code', 'currency'])
                    ?->only(['id', 'name', 'code', 'currency']),
            );
        }

        $businessInfo = app(StorefrontService::class)->businessInfo($currentBranch);

        $branches = $user
            ? Cache::remember('branches.active.minilist', 120, fn () => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Branch $branch) => $branch->only(['id', 'name', 'code']))
                ->values()
                ->all())
            : collect();

        return [
            ...parent::share($request),
            'name' => $businessInfo['name'] ?? config('app.name'),
            'business' => [
                'name' => $businessInfo['name'] ?? config('app.name'),
                'logo' => $businessInfo['logo'] ?? null,
                'tagline' => $businessInfo['tagline'] ?? null,
            ],
            'locale' => $locale,
            'dir' => Locale::dir($locale),
            'availableLocales' => Locale::AVAILABLE,
            'translations' => Locale::translations($locale),
            'money' => app(CurrencyService::class)->moneyConfig(),
            'auth' => [
                'user' => $userData,
                'customer' => $customerData,
                'permissions' => $permissions,
                'dashboardUrl' => $user !== null
                    ? app(PostLoginRedirectService::class)->forUser($user)
                    : null,
            ],
            'currentBranch' => $currentBranch,
            'branches' => $branches,
            'flash' => [
                'toast' => $request->session()->get('toast'),
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'message' => $request->session()->get('message'),
            ],
            'checkout' => $isAdminArea ? null : $request->session()->get('checkout'),
            'cart' => $isAdminArea ? null : app(CartService::class)->summary(),
            'guestProfile' => $isAdminArea ? null : app(GuestProfileService::class)->get(),
            'favoriteProductIds' => $favoriteProductIds,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
