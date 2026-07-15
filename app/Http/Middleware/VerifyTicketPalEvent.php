<?php

namespace App\Http\Middleware;

use App\Models\IntegrationEvent;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyTicketPalEvent
{
    private const PROVIDER = 'ticketpal';

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('encore.ticketpal.secret', '');
        $externalEventId = (string) $request->header('X-TicketPal-Event-ID', '');
        $timestamp = (string) $request->header('X-TicketPal-Timestamp', '');
        $providedSignature = (string) $request->header('X-TicketPal-Signature', '');

        if (! $this->hasValidSecurityHeaders($externalEventId, $timestamp, $providedSignature)) {
            return $this->unauthorized('Missing or invalid TicketPal event security headers.');
        }

        if (abs(now()->timestamp - (int) $timestamp) > (int) config('encore.ticketpal.signature_tolerance_seconds', 300)) {
            return $this->unauthorized('TicketPal event timestamp is outside the accepted window.');
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $timestamp.'.'.$externalEventId.'.'.$payload, $secret);
        $normalizedSignature = Str::startsWith($providedSignature, 'sha256=')
            ? Str::after($providedSignature, 'sha256=')
            : $providedSignature;

        if ($secret === '' || ! hash_equals($expectedSignature, $normalizedSignature)) {
            return $this->unauthorized('Invalid TicketPal event signature.');
        }

        $payloadHash = hash('sha256', $payload);
        $eventType = (string) ($request->route()?->getName() ?? $request->route()?->uri() ?? 'unknown');
        [$event, $shouldProcess, $conflict] = $this->registerOrResolveEvent(
            $eventType,
            $externalEventId,
            $payloadHash
        );

        if ($conflict !== null) {
            return $conflict;
        }

        if (! $shouldProcess) {
            return $this->replayResponse($event);
        }

        $request->attributes->set('provider_event_record_id', $event->id);
        $request->attributes->set('correlation_id', $event->correlation_id);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $event->update([
                'status' => 'failed',
                'error_message' => $exception::class,
            ]);

            throw $exception;
        }

        if ($response->getStatusCode() >= 500) {
            $event->update([
                'status' => 'failed',
                'error_message' => 'HTTP '.$response->getStatusCode(),
            ]);
        } else {
            $event->update([
                'status' => 'processed',
                'processed_at' => now(),
                'error_message' => null,
                'response_body' => Crypt::encryptString((string) $response->getContent()),
                'response_status' => $response->getStatusCode(),
                'response_expires_at' => now()->addSeconds(
                    (int) config('encore.ticketpal.response_retention_seconds', 604800)
                ),
            ]);
        }

        $response->headers->set('X-Correlation-ID', $event->correlation_id);

        return $response;
    }

    private function hasValidSecurityHeaders(string $eventId, string $timestamp, string $signature): bool
    {
        return $eventId !== ''
            && mb_strlen($eventId) <= 255
            && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]*$/', $eventId) === 1
            && preg_match('/^\d{10}$/', $timestamp) === 1
            && preg_match('/^(?:sha256=)?[a-f0-9]{64}$/i', $signature) === 1;
    }

    /**
     * @return array{IntegrationEvent, bool, ?Response}
     */
    private function registerOrResolveEvent(
        string $eventType,
        string $externalEventId,
        string $payloadHash
    ): array {
        $eventId = (string) Str::uuid();
        $inserted = IntegrationEvent::query()->insertOrIgnore([
            'id' => $eventId,
            'provider' => self::PROVIDER,
            'event_type' => $eventType,
            'external_event_id' => $externalEventId,
            'payload_hash' => $payloadHash,
            'received_at' => now(),
            'status' => 'processing',
            'attempts' => 1,
            'correlation_id' => (string) Str::uuid(),
        ]);

        if ($inserted === 1) {
            return [IntegrationEvent::query()->findOrFail($eventId), true, null];
        }

        return DB::transaction(function () use ($externalEventId, $payloadHash): array {
            $event = IntegrationEvent::query()
                ->where('provider', self::PROVIDER)
                ->where('external_event_id', $externalEventId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! hash_equals($event->payload_hash, $payloadHash)) {
                return [$event, false, new JsonResponse([
                    'ok' => false,
                    'message' => 'TicketPal event ID was reused with a different payload.',
                    'correlation_id' => $event->correlation_id,
                ], Response::HTTP_CONFLICT)];
            }

            if ($event->status === 'failed'
                && $event->attempts < (int) config('encore.ticketpal.max_event_attempts', 3)) {
                $event->update([
                    'status' => 'processing',
                    'attempts' => $event->attempts + 1,
                    'error_message' => null,
                ]);

                return [$event->refresh(), true, null];
            }

            return [$event, false, null];
        });
    }

    private function replayResponse(IntegrationEvent $event): Response
    {
        if ($event->status === 'processing') {
            return (new JsonResponse([
                'ok' => false,
                'message' => 'TicketPal event processing is already in progress.',
                'correlation_id' => $event->correlation_id,
            ], Response::HTTP_CONFLICT))->withHeaders([
                'Retry-After' => '1',
                'X-Correlation-ID' => $event->correlation_id,
            ]);
        }

        if ($event->status === 'failed') {
            return (new JsonResponse([
                'ok' => false,
                'message' => 'TicketPal event processing failed and cannot be retried automatically.',
                'correlation_id' => $event->correlation_id,
            ], Response::HTTP_CONFLICT))->withHeaders([
                'X-Correlation-ID' => $event->correlation_id,
            ]);
        }

        if ($event->response_body === null
            || $event->response_status === null
            || $event->response_expires_at?->isPast()) {
            return (new JsonResponse([
                'ok' => false,
                'message' => 'TicketPal event was already processed and its replay response has expired.',
                'correlation_id' => $event->correlation_id,
            ], Response::HTTP_CONFLICT))->withHeaders([
                'X-Correlation-ID' => $event->correlation_id,
                'X-Provider-Event-Replayed' => 'true',
            ]);
        }

        try {
            $body = Crypt::decryptString($event->response_body);
        } catch (DecryptException) {
            return (new JsonResponse([
                'ok' => false,
                'message' => 'TicketPal event replay response is unavailable.',
                'correlation_id' => $event->correlation_id,
            ], Response::HTTP_CONFLICT))->withHeaders([
                'X-Correlation-ID' => $event->correlation_id,
            ]);
        }

        return response($body, $event->response_status, [
            'Content-Type' => 'application/json',
            'X-Correlation-ID' => $event->correlation_id,
            'X-Provider-Event-Replayed' => 'true',
        ]);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return new JsonResponse([
            'ok' => false,
            'message' => $message,
        ], Response::HTTP_UNAUTHORIZED);
    }
}
