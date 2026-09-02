<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Locale::normalize(
            $request->session()->get('locale')
                ?? $request->cookie('locale')
                ?? config('app.locale', Locale::DEFAULT)
        );

        app()->setLocale($locale);
        $request->attributes->set('locale', $locale);
        $request->attributes->set('dir', Locale::dir($locale));

        return $next($request);
    }
}
