<?php

namespace App\Http\Controllers\Api\V2;

use App\Application\Catalogue\ProviderV2CatalogueImportService;
use App\Domain\Integration\ProviderAuthority;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as LaravelValidator;
use RuntimeException;

final class CatalogueImportController extends Controller
{
    public function __construct(private readonly ProviderV2CatalogueImportService $service) {}

    public function organisation(Request $request): JsonResponse
    {
        return $this->handle($request, [
            ...$this->envelopeRules($request),
            'provider_organisation_id' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive', 'archived', 'deleted'])],
        ], fn (ProviderAuthority $authority, array $payload, string $key, string $digest, string $correlationId): array => $this->service->upsertOrganisation($authority, $payload, $key, $digest, $correlationId));
    }

    public function membership(Request $request): JsonResponse
    {
        return $this->handle($request, [
            ...$this->envelopeRules($request),
            'provider_organisation_id' => ['required', 'string', 'max:255'],
            'provider_user_id' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:320'],
            'role' => ['required', Rule::in(['owner', 'administrator'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'archived', 'deleted'])],
        ], fn (ProviderAuthority $authority, array $payload, string $key, string $digest, string $correlationId): array => $this->service->upsertMembership($authority, $payload, $key, $digest, $correlationId));
    }

    public function show(Request $request): JsonResponse
    {
        return $this->handle($request, [
            ...$this->envelopeRules($request),
            'provider_organisation_id' => ['required', 'string', 'max:255'],
            'provider_show_id' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in([
                'draft', 'published', 'upcoming', 'now_playing', 'ended', 'cancelled', 'archived', 'deleted',
            ])],
            'image_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'public_url' => ['required', 'url', 'max:2048'],
        ], fn (ProviderAuthority $authority, array $payload, string $key, string $digest, string $correlationId): array => $this->service->upsertShow($authority, $payload, $key, $digest, $correlationId));
    }

    public function performance(Request $request): JsonResponse
    {
        return $this->handle($request, [
            ...$this->envelopeRules($request),
            'provider_show_id' => ['required', 'string', 'max:255'],
            'provider_performance_id' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after:starts_at'],
            'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled', 'archived', 'deleted'])],
            'location' => ['required', 'array'],
            'location.type' => ['required', Rule::in(['venue', 'activity'])],
            'location.name' => ['required', 'string', 'max:255'],
            'location.city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location.postcode' => ['sometimes', 'nullable', 'string', 'max:32'],
            'location.country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location.public_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
        ], fn (ProviderAuthority $authority, array $payload, string $key, string $digest, string $correlationId): array => $this->service->upsertPerformance($authority, $payload, $key, $digest, $correlationId));
    }

    /** @param array<string, mixed> $rules
     * @param  callable(ProviderAuthority, array<string, mixed>, string, string, string): array<string, mixed>  $operation
     */
    private function handle(Request $request, array $rules, callable $operation): JsonResponse
    {
        $authority = $request->attributes->get('provider_authority');
        assert($authority instanceof ProviderAuthority);
        $validator = Validator::make($request->json()->all(), $rules);
        if ($validator->fails()) {
            return $this->validationFailure($request, $validator);
        }

        try {
            $result = $operation(
                $authority,
                $validator->validated(),
                (string) $request->header('Idempotency-Key'),
                (string) $request->attributes->get('body_digest'),
                (string) $request->attributes->get('correlation_id'),
            );
        } catch (RuntimeException $exception) {
            report($exception);

            return $this->error($request, 'temporarily_unavailable', 'The provider operation is temporarily unavailable.', 503);
        }

        return match ($result['error'] ?? null) {
            'idempotency_conflict' => $this->error($request, 'idempotency_conflict',
                'The idempotency key was already used with different request content.', 409),
            'mapping_conflict' => $this->error($request, 'mapping_conflict',
                (string) ($result['message'] ?? 'The provider mapping conflicts with an existing resource.'), 409),
            'mapping_not_found' => response()->json([
                'error' => 'validation_failed',
                'message' => (string) ($result['message'] ?? 'A required provider resource mapping could not be resolved.'),
                'correlation_id' => $request->attributes->get('correlation_id'),
                'details' => [['field' => 'provider_external_id', 'code' => 'mapping_not_found']],
            ], 422),
            default => response()->json($result, 202),
        };
    }

    /** @return array<string, mixed> */
    private function envelopeRules(Request $request): array
    {
        $authority = $request->attributes->get('provider_authority');
        assert($authority instanceof ProviderAuthority);

        return [
            'schema_version' => ['required', Rule::in(['2.0'])],
            'provider' => ['required', Rule::in([$authority->providerSlug])],
        ];
    }

    private function validationFailure(Request $request, LaravelValidator $validator): JsonResponse
    {
        $details = [];
        foreach ($validator->errors()->toArray() as $field => $messages) {
            $details[] = ['field' => $field, 'code' => 'invalid'];
        }

        return response()->json([
            'error' => 'validation_failed',
            'message' => 'The request failed validation.',
            'correlation_id' => $request->attributes->get('correlation_id'),
            'details' => $details,
        ], 422);
    }

    private function error(Request $request, string $error, string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => $error,
            'message' => $message,
            'correlation_id' => $request->attributes->get('correlation_id'),
        ], $status);
    }
}
