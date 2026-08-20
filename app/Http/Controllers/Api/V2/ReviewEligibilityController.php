<?php

namespace App\Http\Controllers\Api\V2;

use App\Application\ReviewEligibility\ProviderV2ReviewEligibilityService;
use App\Domain\Integration\ProviderAuthority;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

final class ReviewEligibilityController extends Controller
{
    public function __construct(private readonly ProviderV2ReviewEligibilityService $service) {}

    public function eligibility(Request $request): JsonResponse
    {
        $authority = $request->attributes->get('provider_authority');
        assert($authority instanceof ProviderAuthority);
        $validator = Validator::make($request->json()->all(), [
            'event_id' => ['required', 'uuid'], 'schema_version' => ['required', Rule::in(['2.0'])],
            'occurred_at' => ['required', 'date'], 'provider' => ['required', Rule::in([$authority->providerSlug])],
            'provider_booking_id' => ['required', 'string', 'max:100'],
            'provider_show_id' => ['required', 'string', 'max:100'],
            'provider_performance_id' => ['required', 'string', 'max:100'],
            'reviewer.name' => ['required', 'string', 'max:255'],
            'reviewer.email' => ['required', 'email', 'max:320'],
            'admission_quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'consent.purpose' => ['required', Rule::in(['encore_review'])],
            'consent.policy_version' => ['required', 'string', 'max:100'],
            'consent.captured_at' => ['required', 'date'],
        ]);
        if ($validator->fails()) {
            return $this->validationFailure($request, $validator->errors()->toArray());
        }

        try {
            $result = $this->service->acceptEligibility(
                $authority, $validator->validated(), (string) $request->header('Idempotency-Key'),
                $request->attributes->get('body_digest'), $request->attributes->get('correlation_id'),
            );
        } catch (RuntimeException) {
            return $this->error($request, 'temporarily_unavailable',
                'The provider operation is temporarily unavailable.', 503);
        }

        return $this->result($request, $result);
    }

    public function withdrawal(Request $request): JsonResponse
    {
        $authority = $request->attributes->get('provider_authority');
        assert($authority instanceof ProviderAuthority);
        $validator = Validator::make($request->json()->all(), [
            'event_id' => ['required', 'uuid'], 'schema_version' => ['required', Rule::in(['2.0'])],
            'occurred_at' => ['required', 'date'], 'provider' => ['required', Rule::in([$authority->providerSlug])],
            'provider_booking_id' => ['required', 'string', 'max:100'],
            'original_eligibility_event_id' => ['nullable', 'uuid'],
            'purpose' => ['required', Rule::in(['encore_review'])],
            'withdrawn_at' => ['required', 'date'],
        ]);
        if ($validator->fails()) {
            return $this->validationFailure($request, $validator->errors()->toArray());
        }
        $result = $this->service->withdraw(
            $authority, $validator->validated(), (string) $request->header('Idempotency-Key'),
            $request->attributes->get('body_digest'), $request->attributes->get('correlation_id'),
        );

        return $this->result($request, $result);
    }

    /** @param array<string, mixed> $result */
    private function result(Request $request, array $result): JsonResponse
    {
        return match ($result['error'] ?? null) {
            'idempotency_conflict' => $this->error($request, 'idempotency_conflict',
                'The idempotency key was already used with different request content.', 409),
            'mapping_not_found' => response()->json([
                'error' => 'validation_failed',
                'message' => 'A required provider resource mapping could not be resolved.',
                'correlation_id' => $request->attributes->get('correlation_id'),
                'details' => [['field' => 'provider_performance_id', 'code' => 'mapping_not_found']],
            ], 422),
            default => response()->json($result, 202),
        };
    }

    /** @param array<string, list<string>> $errors */
    private function validationFailure(Request $request, array $errors): JsonResponse
    {
        $details = [];
        foreach ($errors as $field => $messages) {
            $details[] = ['field' => $field, 'code' => $field === 'admission_quantity' ? 'minimum' : 'invalid'];
        }

        return response()->json([
            'error' => 'validation_failed', 'message' => 'The request failed validation.',
            'correlation_id' => $request->attributes->get('correlation_id'), 'details' => $details,
        ], 422);
    }

    private function error(Request $request, string $error, string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => $error, 'message' => $message,
            'correlation_id' => $request->attributes->get('correlation_id'),
        ], $status);
    }
}
