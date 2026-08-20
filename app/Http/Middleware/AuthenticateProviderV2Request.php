<?php

namespace App\Http\Middleware;

use App\Contracts\ProviderSecretResolver;
use App\Domain\Integration\ProviderAuthority;
use App\Models\IntegrationCredential;
use App\Models\IntegrationRequestJournal;
use App\Models\IntegrationRequestNonce;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class AuthenticateProviderV2Request
{
    public function __construct(private readonly ProviderSecretResolver $secrets) {}

    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $this->validUuid($request->header('X-Correlation-Id'))
            ? (string) $request->header('X-Correlation-Id')
            : (string) Str::uuid();
        $request->attributes->set('correlation_id', $correlationId);
        $operation = (string) $request->route('provider_operation');
        $required = [
            'X-Provider-Key-Id', 'X-Request-Timestamp', 'X-Request-Nonce',
            'X-Request-Signature', 'Idempotency-Key', 'X-Correlation-Id',
        ];

        foreach ($required as $header) {
            if (! is_string($request->header($header)) || $request->header($header) === '') {
                return $this->error($request, null, $operation, 'bad_request',
                    'The provider request is malformed.', $correlationId, 400, 'missing_header');
            }
        }

        if (! $this->validUuid($request->header('X-Request-Nonce')) || ! $this->validUuid($correlationId)) {
            return $this->error($request, null, $operation, 'bad_request',
                'The provider request is malformed.', $correlationId, 400, 'invalid_header');
        }

        $credential = IntegrationCredential::query()
            ->with('provider')
            ->where('key_id', $request->header('X-Provider-Key-Id'))
            ->first();

        if (! $credential || ! $credential->provider?->is_active || $credential->revoked_at
            || $credential->activated_at->isFuture() || ($credential->expires_at && ! $credential->expires_at->isFuture())) {
            return $this->unauthorised($request, $credential, $operation, $correlationId, 'credential_lifecycle');
        }

        try {
            $timestamp = CarbonImmutable::parse((string) $request->header('X-Request-Timestamp'));
        } catch (Throwable) {
            return $this->unauthorised($request, $credential, $operation, $correlationId, 'invalid_timestamp');
        }

        if (abs($timestamp->diffInSeconds(now(), false)) > config('encore.provider_v2.signature_tolerance_seconds')) {
            return $this->unauthorised($request, $credential, $operation, $correlationId, 'stale_timestamp');
        }

        $secret = $this->secrets->resolve($credential->secret_reference);
        $digest = hash('sha256', $request->getContent());
        $canonical = implode("\n", [
            strtoupper($request->method()), $request->getPathInfo(),
            (string) $request->header('X-Request-Timestamp'),
            (string) $request->header('X-Request-Nonce'), $digest,
        ]);
        $expected = $secret ? 'v1='.hash_hmac('sha256', $canonical, $secret) : null;
        $provided = (string) $request->header('X-Request-Signature');

        if (! $expected || ! preg_match('/^v1=[a-f0-9]{64}$/', $provided) || ! hash_equals($expected, $provided)) {
            return $this->unauthorised($request, $credential, $operation, $correlationId, 'invalid_signature');
        }

        $reserved = IntegrationRequestNonce::query()->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'credential_id' => $credential->id,
            'nonce' => $request->header('X-Request-Nonce'),
            'request_timestamp' => $timestamp,
            'received_at' => now(),
            'expires_at' => now()->addSeconds(config('encore.provider_v2.nonce_retention_seconds')),
            'correlation_id' => $correlationId,
        ]);
        if ($reserved !== 1) {
            return $this->unauthorised($request, $credential, $operation, $correlationId, 'replayed_nonce');
        }

        $authority = new ProviderAuthority(
            $credential,
            $credential->provider->slug,
            $credential->account_reference,
            $credential->operation_scopes,
        );
        if (! $authority->allows($operation)) {
            return $this->error($request, $credential, $operation, 'forbidden',
                'The provider credential is not authorised for this operation.',
                $correlationId, 403, 'operation_scope');
        }

        $request->attributes->set('provider_authority', $authority);
        $request->attributes->set('body_digest', $digest);
        $journal = $this->journal($request, $credential, $operation, 'authenticated', null, $correlationId);
        $response = $next($request);
        if ($journal) {
            $journal->forceFill(['completed_at' => now(), 'response_status' => $response->getStatusCode()])->save();
        }

        $response->headers->set('X-Correlation-Id', $correlationId);

        return $response;
    }

    private function unauthorised(Request $request, ?IntegrationCredential $credential, string $operation, string $correlationId, string $code): JsonResponse
    {
        return $this->error($request, $credential, $operation, 'unauthorised',
            'The provider request could not be authenticated.', $correlationId, 401, $code);
    }

    private function error(Request $request, ?IntegrationCredential $credential, string $operation, string $error, string $message, string $correlationId, int $status, string $failureCode): JsonResponse
    {
        $this->journal($request, $credential, $operation, $error, $failureCode, $correlationId, $status);

        return response()->json([
            'error' => $error, 'message' => $message, 'correlation_id' => $correlationId,
        ], $status, ['X-Correlation-Id' => $correlationId]);
    }

    private function journal(Request $request, ?IntegrationCredential $credential, string $operation, string $outcome, ?string $failureCode, string $correlationId, ?int $status = null): ?IntegrationRequestJournal
    {
        try {
            return IntegrationRequestJournal::create([
                'credential_id' => $credential?->id,
                'provider_id' => $credential?->provider_id,
                'credential_key_fingerprint' => hash('sha256', (string) $request->header('X-Provider-Key-Id')),
                'operation' => $operation ?: 'unknown',
                'method' => $request->method(),
                'path' => $request->getPathInfo(),
                'body_digest' => $request->getContent() === '' ? null : hash('sha256', $request->getContent()),
                'auth_outcome' => $outcome,
                'failure_code' => $failureCode,
                'correlation_id' => $correlationId,
                'received_at' => now(),
                'completed_at' => $status ? now() : null,
                'response_status' => $status,
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    private function validUuid(mixed $value): bool
    {
        return is_string($value) && Str::isUuid($value);
    }
}
