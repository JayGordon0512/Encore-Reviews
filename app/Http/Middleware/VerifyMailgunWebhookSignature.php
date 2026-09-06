<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMailgunWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $timestamp = (string) $request->input('signature.timestamp', '');
        $token = (string) $request->input('signature.token', '');
        $signature = (string) $request->input('signature.signature', '');
        $signingKey = (string) config('encore.mailgun_webhooks.signing_key', '');
        $tolerance = max(1, (int) config('encore.mailgun_webhooks.signature_tolerance_seconds', 300));

        abort_unless(
            $timestamp !== ''
            && ctype_digit($timestamp)
            && $token !== ''
            && $signature !== ''
            && $signingKey !== ''
            && abs(now()->timestamp - (int) $timestamp) <= $tolerance
            && hash_equals(hash_hmac('sha256', $timestamp.$token, $signingKey), $signature),
            401,
        );

        $request->attributes->set('mailgun_signature_token_digest', hash('sha256', $token));

        return $next($request);
    }
}
