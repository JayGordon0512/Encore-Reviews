<?php

namespace App\Application\ReviewEligibility;

use App\Application\Invitations\DetermineInvitationScheduleTime;
use App\Domain\Integration\ProviderAuthority;
use App\Domain\ReviewEligibility\EligibilityIdGenerator;
use App\Models\AuditLog;
use App\Models\IntegrationIdempotencyRecord;
use App\Models\IntegrationPerformanceMapping;
use App\Models\OutboxMessage;
use App\Models\ProtectedReviewerContact;
use App\Models\ReviewConsentEvidence;
use App\Models\ReviewEligibility;
use App\Models\ReviewEligibilityLifecycleEvent;
use App\Models\ReviewEligibilityWithdrawal;
use App\Models\ReviewInvitation;
use App\Models\ReviewInvitationSchedule;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class ProviderV2ReviewEligibilityService
{
    public function __construct(
        private readonly EligibilityIdGenerator $eligibilityIds,
        private readonly DetermineInvitationScheduleTime $scheduleTime,
    ) {}

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function acceptEligibility(ProviderAuthority $authority, array $payload, string $idempotencyKey, string $digest, string $correlationId): array
    {
        return DB::transaction(function () use ($authority, $payload, $idempotencyKey, $digest, $correlationId): array {
            $existing = $this->idempotency($authority, 'review-eligibility:write', $idempotencyKey);
            if ($existing) {
                return $this->existingEligibilityOutcome($existing, $digest, $correlationId);
            }

            $mapping = IntegrationPerformanceMapping::query()
                ->with('showMapping')
                ->where('provider_id', $authority->credential->provider_id)
                ->where('account_reference', $authority->accountReference)
                ->where('external_performance_id', $payload['provider_performance_id'])
                ->first();

            if (! $mapping || $mapping->showMapping?->external_show_id !== $payload['provider_show_id']
                || ! $authority->credential->organisations()->whereKey($mapping->organisation_id)->exists()) {
                return ['error' => 'mapping_not_found'];
            }

            $record = $this->reserveIdempotency($authority, 'review-eligibility:write', $idempotencyKey, $digest, $correlationId);
            if (is_array($record)) {
                return $record;
            }
            $fingerprintKey = config('encore.provider_v2.contact_fingerprint_key');
            if (! is_string($fingerprintKey) || $fingerprintKey === '') {
                throw new RuntimeException('Contact fingerprint key is not configured.');
            }

            $email = Str::lower(trim($payload['reviewer']['email']));
            $fingerprintVersion = (int) config('encore.provider_v2.contact_fingerprint_version');
            $fingerprint = hash_hmac('sha256', $email, $fingerprintKey);
            $contact = ProtectedReviewerContact::query()->firstOrCreate(
                ['fingerprint_version' => $fingerprintVersion, 'email_fingerprint' => $fingerprint],
                [
                    'email_ciphertext' => Crypt::encryptString($email),
                    'display_name_ciphertext' => Crypt::encryptString(trim($payload['reviewer']['name'])),
                    'status' => 'active',
                ],
            );
            $consent = ReviewConsentEvidence::create([
                'provider_id' => $authority->credential->provider_id,
                'credential_id' => $authority->credential->id,
                'organisation_id' => $mapping->organisation_id,
                'account_reference' => $authority->accountReference,
                'provider_event_id' => $payload['event_id'],
                'provider_booking_id' => $payload['provider_booking_id'],
                'purpose' => $payload['consent']['purpose'],
                'policy_version' => $payload['consent']['policy_version'],
                'captured_at' => $payload['consent']['captured_at'],
                'evidence_digest' => $digest,
                'created_at' => now(),
            ]);
            $eligibility = ReviewEligibility::create([
                'id' => $this->eligibilityIds->generate($payload['event_id']),
                'provider_id' => $authority->credential->provider_id,
                'credential_id' => $authority->credential->id,
                'organisation_id' => $mapping->organisation_id,
                'account_reference' => $authority->accountReference,
                'show_id' => $mapping->show_id,
                'performance_id' => $mapping->performance_id,
                'contact_id' => $contact->id,
                'consent_evidence_id' => $consent->id,
                'provider_event_id' => $payload['event_id'],
                'provider_booking_id' => $payload['provider_booking_id'],
                'purpose' => $payload['consent']['purpose'],
                'admission_quantity' => $payload['admission_quantity'],
                'status' => 'eligible_pending_attendance',
                'occurred_at' => $payload['occurred_at'],
            ]);
            $performance = $mapping->performance()->firstOrFail();
            $scheduleAt = $this->scheduleTime->forPerformance(
                $performance,
                (int) config('encore.provider_v2.invitation_delay_hours'),
            );
            ReviewInvitationSchedule::create([
                'eligibility_id' => $eligibility->id,
                'source' => 'provider_v2',
                'correlation_id' => $correlationId,
                'scheduled_for' => $scheduleAt,
                'status' => 'suppressed',
                'suppression_reason' => 'attendance_pending',
            ]);
            $this->outbox('ReviewEligibilityAccepted', 'ReviewEligibility', $eligibility->id,
                $mapping->organisation_id, $authority, $correlationId, ['eligibility_id' => $eligibility->id]);
            $this->audit($mapping->organisation_id, 'review_eligibility.accepted', $eligibility->id, $correlationId);

            $record->forceFill([
                'status' => 'completed', 'outcome_type' => 'review_eligibility',
                'outcome_id' => $eligibility->id, 'response_status' => 202,
            ])->save();

            return [
                'status' => 'accepted', 'event_id' => $payload['event_id'],
                'eligibility_id' => $eligibility->id, 'correlation_id' => $correlationId,
            ];
        });
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function withdraw(ProviderAuthority $authority, array $payload, string $idempotencyKey, string $digest, string $correlationId): array
    {
        return DB::transaction(function () use ($authority, $payload, $idempotencyKey, $digest, $correlationId): array {
            $existing = $this->idempotency($authority, 'review-withdrawal:write', $idempotencyKey);
            if ($existing) {
                return $this->existingWithdrawalOutcome($existing, $digest, $correlationId);
            }

            $record = $this->reserveIdempotency($authority, 'review-withdrawal:write', $idempotencyKey, $digest, $correlationId);
            if (is_array($record)) {
                return $record;
            }
            $eligibility = ReviewEligibility::query()
                ->where('provider_id', $authority->credential->provider_id)
                ->where('account_reference', $authority->accountReference)
                ->where('provider_booking_id', $payload['provider_booking_id'])
                ->where('purpose', $payload['purpose'])
                ->lockForUpdate()
                ->first();

            if ($eligibility) {
                $eligibility->forceFill(['status' => 'withdrawn', 'withdrawn_at' => $payload['withdrawn_at']])->save();
                ReviewInvitationSchedule::query()->where('eligibility_id', $eligibility->id)
                    ->whereIn('status', ['scheduled', 'suppressed', 'processing', 'issued'])
                    ->update(['status' => 'cancelled', 'suppression_reason' => 'consent_withdrawn', 'cancelled_at' => now()]);
                ReviewInvitation::query()
                    ->where('eligibility_id', $eligibility->id)
                    ->whereNull('used_at')
                    ->whereNull('revoked_at')
                    ->update([
                        'status' => 'revoked',
                        'revoked_at' => now(),
                        'revocation_reason' => 'consent_withdrawn',
                        'updated_at' => now(),
                    ]);
            }

            $withdrawal = ReviewEligibilityWithdrawal::create([
                'provider_id' => $authority->credential->provider_id,
                'credential_id' => $authority->credential->id,
                'eligibility_id' => $eligibility?->id,
                'account_reference' => $authority->accountReference,
                'provider_event_id' => $payload['event_id'],
                'original_eligibility_event_id' => $payload['original_eligibility_event_id'] ?? null,
                'provider_booking_id' => $payload['provider_booking_id'],
                'purpose' => $payload['purpose'],
                'withdrawn_at' => $payload['withdrawn_at'],
                'created_at' => now(),
            ]);
            $this->outbox('ReviewEligibilityWithdrawn', 'ReviewEligibilityWithdrawal', $withdrawal->id,
                $eligibility?->organisation_id, $authority, $correlationId,
                ['withdrawal_id' => $withdrawal->id, 'eligibility_id' => $eligibility?->id]);
            if ($eligibility) {
                $this->audit($eligibility->organisation_id, 'review_eligibility.withdrawn', $eligibility->id, $correlationId);
            }
            $record->forceFill([
                'status' => 'completed', 'outcome_type' => 'review_eligibility_withdrawal',
                'outcome_id' => $withdrawal->id, 'response_status' => 202,
            ])->save();

            return ['status' => 'accepted', 'event_id' => $payload['event_id'], 'correlation_id' => $correlationId];
        });
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function lifecycle(ProviderAuthority $authority, array $payload, string $idempotencyKey, string $digest, string $correlationId): array
    {
        return DB::transaction(function () use ($authority, $payload, $idempotencyKey, $digest, $correlationId): array {
            $existing = $this->idempotency($authority, 'review-eligibility-lifecycle:write', $idempotencyKey);
            if ($existing) {
                return $this->existingLifecycleOutcome($existing, $digest, $correlationId);
            }

            $eligibility = ReviewEligibility::query()
                ->where('provider_id', $authority->credential->provider_id)
                ->where('account_reference', $authority->accountReference)
                ->where('provider_booking_id', $payload['provider_booking_id'])
                ->where('purpose', 'encore_review')
                ->lockForUpdate()
                ->first();
            if (! $eligibility) {
                return ['error' => 'eligibility_not_found'];
            }

            $record = $this->reserveIdempotency(
                $authority,
                'review-eligibility-lifecycle:write',
                $idempotencyKey,
                $digest,
                $correlationId,
            );
            if (is_array($record)) {
                return $record;
            }

            $eventType = $payload['event_type'];
            $mapping = null;
            if ($eventType === 'performance_changed') {
                $mapping = IntegrationPerformanceMapping::query()
                    ->with('showMapping')
                    ->where('provider_id', $authority->credential->provider_id)
                    ->where('account_reference', $authority->accountReference)
                    ->where('external_performance_id', $payload['provider_performance_id'])
                    ->first();
                if (! $mapping || $mapping->showMapping?->external_show_id !== $payload['provider_show_id']
                    || ! $authority->credential->organisations()->whereKey($mapping->organisation_id)->exists()) {
                    throw new RuntimeException('The replacement performance mapping could not be resolved.');
                }
            }

            if (in_array($eventType, ['eligibility_updated', 'admission_quantity_changed'], true)
                && array_key_exists('admission_quantity', $payload)) {
                $eligibility->admission_quantity = $payload['admission_quantity'];
            } elseif ($eventType === 'attendance_confirmed') {
                $eligibility->status = 'verified_eligible';
                $eligibility->verified_at = $payload['occurred_at'];
                if (array_key_exists('admission_quantity', $payload)) {
                    $eligibility->admission_quantity = $payload['admission_quantity'];
                }
            } elseif ($eventType === 'performance_changed' && $mapping) {
                $this->revokeUnusedInvitations($eligibility, 'performance_changed');
                $eligibility->forceFill([
                    'organisation_id' => $mapping->organisation_id,
                    'show_id' => $mapping->show_id,
                    'performance_id' => $mapping->performance_id,
                    'admission_quantity' => $payload['admission_quantity'] ?? $eligibility->admission_quantity,
                ]);
            } elseif ($eventType === 'eligibility_revoked') {
                $eligibility->forceFill([
                    'status' => 'revoked',
                    'revoked_at' => $payload['occurred_at'],
                    'revocation_reason' => $payload['reason'],
                ]);
                $this->cancelSchedule($eligibility, $payload['reason']);
                $this->revokeUnusedInvitations($eligibility, $payload['reason']);
            }
            $eligibility->occurred_at = $payload['occurred_at'];
            $eligibility->save();
            if (in_array($eventType, ['attendance_confirmed', 'performance_changed'], true)) {
                $this->scheduleEligibleInvitation($eligibility, $correlationId);
            }

            $event = ReviewEligibilityLifecycleEvent::create([
                'provider_id' => $authority->credential->provider_id,
                'credential_id' => $authority->credential->id,
                'eligibility_id' => $eligibility->id,
                'account_reference' => $authority->accountReference,
                'provider_event_id' => $payload['event_id'],
                'provider_booking_id' => $payload['provider_booking_id'],
                'event_type' => $eventType,
                'reason' => $payload['reason'] ?? null,
                'admission_quantity' => $payload['admission_quantity'] ?? null,
                'provider_show_id' => $payload['provider_show_id'] ?? null,
                'provider_performance_id' => $payload['provider_performance_id'] ?? null,
                'occurred_at' => $payload['occurred_at'],
                'created_at' => now(),
            ]);
            $this->outbox('ReviewEligibilityLifecycleApplied', 'ReviewEligibility', $eligibility->id,
                $eligibility->organisation_id, $authority, $correlationId,
                ['eligibility_id' => $eligibility->id, 'lifecycle_event_id' => $event->id, 'event_type' => $eventType]);
            $this->audit($eligibility->organisation_id, 'review_eligibility.'.$eventType, $eligibility->id, $correlationId);
            $record->forceFill([
                'status' => 'completed', 'outcome_type' => 'review_eligibility_lifecycle',
                'outcome_id' => $event->id, 'response_status' => 202,
            ])->save();

            return [
                'status' => 'accepted', 'event_id' => $payload['event_id'],
                'eligibility_id' => $eligibility->id, 'eligibility_status' => $eligibility->status,
                'correlation_id' => $correlationId,
            ];
        });
    }

    private function scheduleEligibleInvitation(ReviewEligibility $eligibility, string $correlationId): void
    {
        $performance = $eligibility->performance()->firstOrFail();
        $scheduledFor = $this->scheduleTime->forPerformance(
            $performance,
            (int) config('encore.provider_v2.invitation_delay_hours'),
        );
        if ($scheduledFor->isPast()) {
            $scheduledFor = now();
        }
        $verified = $eligibility->status === 'verified_eligible';
        $issuing = (bool) config('encore.provider_v2.invitation_issuing_enabled');
        ReviewInvitationSchedule::query()->where('eligibility_id', $eligibility->id)->update([
            'scheduled_for' => $scheduledFor,
            'status' => $verified && $issuing ? 'scheduled' : 'suppressed',
            'suppression_reason' => $verified
                ? ($issuing ? null : 'invitation_issuing_disabled')
                : 'attendance_pending',
            'claimed_at' => null,
            'cancelled_at' => null,
            'correlation_id' => $correlationId,
            'updated_at' => now(),
        ]);
    }

    private function cancelSchedule(ReviewEligibility $eligibility, string $reason): void
    {
        ReviewInvitationSchedule::query()->where('eligibility_id', $eligibility->id)
            ->whereIn('status', ['scheduled', 'suppressed', 'processing', 'issued'])
            ->update([
                'status' => 'cancelled', 'suppression_reason' => $reason,
                'cancelled_at' => now(), 'claimed_at' => null, 'updated_at' => now(),
            ]);
    }

    private function revokeUnusedInvitations(ReviewEligibility $eligibility, string $reason): void
    {
        ReviewInvitation::query()->where('eligibility_id', $eligibility->id)
            ->whereNull('used_at')->whereNull('revoked_at')
            ->update([
                'status' => 'revoked', 'revoked_at' => now(),
                'revocation_reason' => $reason, 'updated_at' => now(),
            ]);
    }

    private function idempotency(ProviderAuthority $authority, string $operation, string $key): ?IntegrationIdempotencyRecord
    {
        return IntegrationIdempotencyRecord::query()
            ->where('credential_id', $authority->credential->id)
            ->where('operation', $operation)->where('idempotency_key', $key)
            ->lockForUpdate()->first();
    }

    /** @return IntegrationIdempotencyRecord|array<string, mixed> */
    private function reserveIdempotency(ProviderAuthority $authority, string $operation, string $key, string $digest, string $correlationId): IntegrationIdempotencyRecord|array
    {
        $id = (string) Str::uuid();
        $inserted = IntegrationIdempotencyRecord::query()->insertOrIgnore([
            'id' => $id,
            'credential_id' => $authority->credential->id, 'operation' => $operation,
            'idempotency_key' => $key, 'request_digest' => $digest, 'status' => 'processing',
            'first_correlation_id' => $correlationId, 'last_correlation_id' => $correlationId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($inserted === 1) {
            return IntegrationIdempotencyRecord::findOrFail($id);
        }

        $existing = $this->idempotency($authority, $operation, $key);
        if (! $existing) {
            throw new RuntimeException('Idempotency reservation could not be resolved.');
        }

        return match ($operation) {
            'review-eligibility:write' => $this->existingEligibilityOutcome($existing, $digest, $correlationId),
            'review-withdrawal:write' => $this->existingWithdrawalOutcome($existing, $digest, $correlationId),
            'review-eligibility-lifecycle:write' => $this->existingLifecycleOutcome($existing, $digest, $correlationId),
            default => throw new RuntimeException('Unsupported provider idempotency operation.'),
        };
    }

    /** @return array<string, mixed> */
    private function existingEligibilityOutcome(IntegrationIdempotencyRecord $record, string $digest, string $correlationId): array
    {
        if (! hash_equals($record->request_digest, $digest)) {
            return ['error' => 'idempotency_conflict'];
        }
        $eligibility = ReviewEligibility::findOrFail($record->outcome_id);
        $record->forceFill(['last_correlation_id' => $correlationId])->save();

        return ['status' => 'duplicate', 'event_id' => $eligibility->provider_event_id,
            'eligibility_id' => $eligibility->id, 'correlation_id' => $correlationId];
    }

    /** @return array<string, mixed> */
    private function existingWithdrawalOutcome(IntegrationIdempotencyRecord $record, string $digest, string $correlationId): array
    {
        if (! hash_equals($record->request_digest, $digest)) {
            return ['error' => 'idempotency_conflict'];
        }
        $withdrawal = ReviewEligibilityWithdrawal::findOrFail($record->outcome_id);
        $record->forceFill(['last_correlation_id' => $correlationId])->save();

        return ['status' => 'duplicate', 'event_id' => $withdrawal->provider_event_id, 'correlation_id' => $correlationId];
    }

    /** @return array<string, mixed> */
    private function existingLifecycleOutcome(IntegrationIdempotencyRecord $record, string $digest, string $correlationId): array
    {
        if (! hash_equals($record->request_digest, $digest)) {
            return ['error' => 'idempotency_conflict'];
        }
        $event = ReviewEligibilityLifecycleEvent::findOrFail($record->outcome_id);
        $eligibility = ReviewEligibility::findOrFail($event->eligibility_id);
        $record->forceFill(['last_correlation_id' => $correlationId])->save();

        return [
            'status' => 'duplicate', 'event_id' => $event->provider_event_id,
            'eligibility_id' => $eligibility->id, 'eligibility_status' => $eligibility->status,
            'correlation_id' => $correlationId,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function outbox(string $eventType, string $aggregateType, string $aggregateId, ?string $organisationId, ProviderAuthority $authority, string $correlationId, array $payload): void
    {
        OutboxMessage::create([
            'event_type' => $eventType, 'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId, 'organisation_id' => $organisationId,
            'provider_id' => $authority->credential->provider_id, 'payload_version' => 1,
            'payload' => $payload, 'correlation_id' => $correlationId,
            'occurred_at' => now(), 'available_at' => now(),
        ]);
    }

    private function audit(string $organisationId, string $action, string $entityId, string $correlationId): void
    {
        AuditLog::create([
            'organisation_id' => $organisationId, 'user_id' => null, 'action' => $action,
            'entity_type' => 'review_eligibility', 'entity_id' => $entityId,
            'correlation_id' => $correlationId, 'created_at' => now(),
        ]);
    }
}
