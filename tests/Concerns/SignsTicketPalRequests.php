<?php

namespace Tests\Concerns;

use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

trait SignsTicketPalRequests
{
    protected function postTicketPalJson(
        string $uri,
        array $payload,
        ?string $eventId = null,
        ?int $timestamp = null
    ): TestResponse {
        $eventId ??= (string) Str::uuid();
        $timestamp ??= now()->timestamp;
        $secret = (string) config('encore.ticketpal.secret');
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $timestamp.'.'.$eventId.'.'.$body, $secret);

        return $this->withHeaders([
            'X-TicketPal-Secret' => $secret,
            'X-TicketPal-Event-ID' => $eventId,
            'X-TicketPal-Timestamp' => (string) $timestamp,
            'X-TicketPal-Signature' => 'sha256='.$signature,
        ])->postJson($uri, $payload);
    }
}
