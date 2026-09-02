<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerPasswordIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = auth('customer')->user();

        if ($customer !== null && ! $customer->hasPassword()) {
            return redirect()->route('customer.password.setup')
                ->with('toast', [
                    'type' => 'info',
                    'message' => __('toast.password_setup_required'),
                ]);
        }

        return $next($request);
    }
}
