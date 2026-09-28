<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            abort(401);
        }

        if (! in_array($request->user()->role, $roles, true)) {
            abort(403);
        }

        // Blocked account: cannot act, cannot browse authenticated areas.
        if ($request->user()->status === 'suspended') {
            abort(403, 'Votre compte est suspendu. Contactez le support AgroNextZone.');
        }

        return $next($request);
    }
}
