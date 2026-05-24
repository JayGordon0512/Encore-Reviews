<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTicketPalSecret
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('encore.ticketpal.secret', '');
        $provided = (string) $request->header('X-TicketPal-Secret', '');

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
