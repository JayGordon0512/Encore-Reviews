<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user?->is_active, 403, 'This user account is inactive.');

        if (! $user->isSuperAdmin()) {
            abort_unless($user->organisation?->is_active, 403, 'This organisation is inactive.');
        }

        return $next($request);
    }
}
