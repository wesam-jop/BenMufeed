<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CachePublicMediaHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $path = $request->path();

        if (! $this->isCacheableAsset($path)) {
            return $response;
        }

        $maxAge = (int) config('store.media_cache_max_age', 31536000);
        $response->headers->set('Cache-Control', "public, max-age={$maxAge}, immutable");

        return $response;
    }

    protected function isCacheableAsset(string $path): bool
    {
        if (str_starts_with($path, 'storage/')) {
            return true;
        }

        if (str_starts_with($path, 'build/')) {
            return preg_match('/\.(css|js|woff2?|webp|jpe?g|png|gif|svg|ico)$/i', $path) === 1;
        }

        return false;
    }
}
