<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|SymfonyResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();

        return $this->postLoginRedirect($request, $user);
    }

    /**
     * POS والمطبخ وFilament صفحات Livewire/Blade — ليست Inertia.
     * بدون inertia_location يحاول Inertia فتح /pos داخل واجهة المتجر (مودال/بدون تنسيق).
     */
    protected function postLoginRedirect(Request $request, User $user): RedirectResponse|SymfonyResponse
    {
        $destination = $this->postLoginDestination($request, $user);

        if ($request->inertia()) {
            return inertia_location($destination);
        }

        return redirect($destination);
    }

    protected function postLoginDestination(Request $request, User $user): string
    {
        if ($user->isAdmin()) {
            $intended = $request->session()->pull('url.intended');

            if (filled($intended)) {
                return $intended;
            }
        }

        return $user->homeUrl();
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
