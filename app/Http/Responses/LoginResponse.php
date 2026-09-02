<?php

namespace App\Http\Responses;

use App\Services\Shared\PostLoginRedirectService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function __construct(
        protected PostLoginRedirectService $redirects,
    ) {}

    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        return redirect()->intended($this->redirects->forUser($request->user()));
    }
}
