<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Rules\ValidWhatsAppNumber;
use App\Services\Shared\PhoneNumberService;
use App\Services\Shared\PostLoginRedirectService;
use App\Services\Storefront\StorefrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class CustomerAuthController extends Controller
{
    public function __construct(
        protected PostLoginRedirectService $redirects,
        protected StorefrontService $storefront,
        protected PhoneNumberService $phones,
    ) {}

    public function showRegister(): Response|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect($this->redirects->forUser(Auth::guard('web')->user()));
        }

        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.home');
        }

        return Inertia::render('customer/auth/register', $this->authPageProps());
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'whatsapp' => ['required', 'string', new ValidWhatsAppNumber],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $e164 = $this->phones->normalize($request->input('whatsapp'));
        $variants = $this->phones->lookupVariants($request->input('whatsapp'));

        $exists = Customer::query()
            ->where(function ($query) use ($variants) {
                $query->whereIn('whatsapp', $variants)
                    ->orWhereIn('phone', $variants);
            })
            ->exists();

        if ($exists || $e164 === null) {
            throw ValidationException::withMessages([
                'whatsapp' => [__('auth.whatsapp_taken')],
            ]);
        }

        $customer = Customer::create([
            'phone' => $e164,
            'whatsapp' => $e164,
            'name' => $request->input('name') ?: null,
            'password' => $request->input('password'),
        ]);

        Auth::guard('web')->logout();
        Auth::guard('customer')->login($customer);

        return redirect()->route('customer.home');
    }

    public function showLogin(Request $request): Response|RedirectResponse
    {
        if ($user = Auth::guard('web')->user()) {
            return redirect()->intended($this->redirects->forUser($user));
        }

        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.home');
        }

        return Inertia::render('customer/auth/login', [
            ...$this->authPageProps(),
            'status' => $request->session()->get('status'),
            'canResetPassword' => true,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($validated['login']);
        $password = $validated['password'];

        $staffUser = User::query()
            ->where(function ($query) use ($login) {
                $query->where('username', $login)
                    ->orWhere('email', $login);
            })
            ->first();

        if ($staffUser !== null && $staffUser->is_active && Hash::check($password, $staffUser->password)) {
            if (Features::enabled(Features::twoFactorAuthentication()) && $staffUser->hasEnabledTwoFactorAuthentication()) {
                $request->session()->put('login.id', $staffUser->id);

                return redirect()->route('two-factor.login');
            }

            Auth::guard('customer')->logout();
            Auth::guard('web')->login($staffUser, $request->boolean('remember'));

            return redirect()->intended($this->redirects->forUser($staffUser));
        }

        $variants = $this->phones->lookupVariants($login);
        $variants[] = $login;

        $customer = Customer::query()
            ->where(function ($query) use ($variants, $login) {
                $query->whereIn('phone', $variants)
                    ->orWhereIn('whatsapp', $variants)
                    ->orWhere('email', $login);
            })
            ->first();

        if ($customer !== null && $customer->is_active) {
            if (! $customer->hasPassword()) {
                throw ValidationException::withMessages([
                    'login' => [__('auth.password_not_set')],
                ]);
            }

            if (Hash::check($password, $customer->password)) {
                Auth::guard('web')->logout();
                Auth::guard('customer')->login($customer, $request->boolean('remember'));

                return redirect()->intended(route('customer.home'));
            }
        }

        throw ValidationException::withMessages([
            'login' => [__('auth.failed')],
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            Auth::guard('customer')->logout();
        }

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }

    /** @return array<string, mixed> */
    protected function authPageProps(): array
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
