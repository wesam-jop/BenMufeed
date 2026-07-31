<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\AuthenticateSession as FilamentAuthenticateSession;

class FilamentRedirectSessionToLogin extends FilamentAuthenticateSession
{
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
