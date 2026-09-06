<?php

namespace App\Application\Invitations;

use App\Models\AuditLog;
use App\Models\MailgunDeliveryEvent;
use App\Models\ProtectedReviewerContact;
use App\Models\ReviewInvitation;
use App\Models\ReviewInvitationDelivery;
use App\Models\ReviewInvitationSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProcessMailgunDeliveryEventService
{
    /** @param array<string, mixed> $payload */
    public function process(array $payload, string $signatureTokenDigest): string
    {
        $providerEventId = trim((string) data_get($payload, 'event-data.id', ''));
        $eventType = Str::lower(trim((string) data_get($payload, 'event-data.event', '')));
        $deliveryId = trim((string) data_get($payload, 'event-data.user-variables.encore_delivery_id', ''));
        $eventTimestamp = data_get($payload, 'event-data.timestamp');
        $severity = Str::lower(trim((string) data_get($payload, 'event-data.severity', '')));
        $reasonCode = trim((string) data_get($payload, 'event-data.delivery-status.code', ''));

        if ($providerEventId === '' || mb_strlen($providerEventId) > 255
            || $eventType === '' || ! is_numeric($eventTimestamp)
            || strlen($signatureTokenDigest) !== 64) {
            throw ValidationException::withMessages([
                'event-data' => 'The signed Mailgun event is incomplete.',
            ]);
        }

        $eventAt = CarbonImmutable::createFromTimestampUTC((int) floor((float) $eventTimestamp));
        $severity = $severity === '' ? null : Str::limit($severity, 32, '');
        $reasonCode = $reasonCode === '' ? null : Str::limit($reasonCode, 100, '');

        try {
            return DB::transaction(function () use (
                $providerEventId,
                $signatureTokenDigest,
                $eventType,
                $deliveryId,
                $eventAt,
                $severity,
                $reasonCode,
            ): string {
                $replay = MailgunDeliveryEvent::query()
                    ->where('provider_event_id', $providerEventId)
                    ->orWhere('signature_token_digest', $signatureTokenDigest)
                    ->lockForUpdate()
                    ->first();
                if ($replay) {
                    return 'replayed';
                }

                $supported = in_array($eventType, ['delivered', 'failed', 'complained'], true);
                $deliveryReference = Str::isUuid($deliveryId)
                    ? ReviewInvitationDelivery::query()->find($deliveryId)
                    : null;
                if ($deliveryReference) {
                    ReviewInvitationSchedule::query()->lockForUpdate()->find($deliveryReference->schedule_id);
                    ReviewInvitation::query()->lockForUpdate()->find($deliveryReference->invitation_id);
                }
                $delivery = $deliveryReference
                    ? ReviewInvitationDelivery::query()
                        ->with(['invitation.eligibility', 'invitation.audienceAttendance', 'invitation.performance.show'])
                        ->lockForUpdate()
                        ->find($deliveryReference->id)
                    : null;
                $event = MailgunDeliveryEvent::create([
                    'delivery_id' => $delivery?->id,
                    'provider_event_id' => $providerEventId,
                    'signature_token_digest' => $signatureTokenDigest,
                    'event_type' => Str::limit($eventType, 32, ''),
                    'severity' => $severity,
                    'reason_code' => $reasonCode,
                    'outcome' => ! $supported ? 'ignored' : ($delivery ? 'received' : 'unmatched'),
                    'event_at' => $eventAt,
                    'received_at' => now(),
                ]);

                if (! $supported) {
                    return 'ignored';
                }
                if (! $delivery) {
                    return 'unmatched';
                }

                $outcome = $this->applyDeliveryState($delivery, $eventType, $severity, $reasonCode, $eventAt);
                $event->update(['outcome' => $outcome]);
                $this->audit($delivery, $event, $outcome);

                return $outcome;
            });
        } catch (QueryException $exception) {
            $isReplay = MailgunDeliveryEvent::query()
                ->where('provider_event_id', $providerEventId)
                ->orWhere('signature_token_digest', $signatureTokenDigest)
                ->exists();
            if ($isReplay) {
                return 'replayed';
            }

            throw $exception;
        }
    }

    private function applyDeliveryState(
        ReviewInvitationDelivery $delivery,
        string $eventType,
        ?string $severity,
        ?string $reasonCode,
        CarbonImmutable $eventAt,
    ): string {
        if ($delivery->status === 'complained' && $eventType !== 'complained') {
            return 'ignored_terminal';
        }
        if ($eventType !== 'complained' && $delivery->provider_status_at?->isAfter($eventAt)) {
            return 'ignored_stale';
        }

        if ($eventType === 'delivered') {
            $delivery->update([
                'status' => 'delivered',
                'provider_status_at' => $eventAt,
                'delivered_at' => $eventAt,
                'error_code' => null,
            ]);

            return 'applied';
        }

        if ($eventType === 'failed' && $severity !== 'permanent') {
            $delivery->update([
                'status' => 'temporarily_failed',
                'provider_status_at' => $eventAt,
                'failed_at' => $eventAt,
                'error_code' => $reasonCode ?: 'mailgun_temporary_failure',
            ]);

            return 'applied';
        }

        $reason = $eventType === 'complained' ? 'mailgun_complaint' : 'mailgun_permanent_failure';
        $delivery->update([
            'status' => $eventType === 'complained' ? 'complained' : 'failed',
            'provider_status_at' => $eventAt,
            'failed_at' => $eventType === 'failed' ? $eventAt : $delivery->failed_at,
            'complained_at' => $eventType === 'complained' ? $eventAt : $delivery->complained_at,
            'error_code' => $reasonCode ?: $reason,
        ]);
        $this->suppressContact($delivery->invitation, $reason);

        return 'applied';
    }

    private function suppressContact(ReviewInvitation $invitation, string $reason): void
    {
        $contactId = $invitation->eligibility?->contact_id
            ?? $invitation->audienceAttendance?->contact_id;
        if (! $contactId) {
            return;
        }

        ProtectedReviewerContact::query()->whereKey($contactId)->update([
            'status' => $reason === 'mailgun_complaint' ? 'complained' : 'undeliverable',
            'updated_at' => now(),
        ]);
        ReviewInvitation::query()
            ->whereKey($invitation->id)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'revocation_reason' => $reason,
                'updated_at' => now(),
            ]);
        ReviewInvitationSchedule::query()
            ->where(function ($query) use ($contactId): void {
                $query->whereHas('eligibility', fn ($query) => $query->where('contact_id', $contactId))
                    ->orWhereHas('audienceAttendance', fn ($query) => $query->where('contact_id', $contactId));
            })
            ->where(function ($query): void {
                $query->whereIn('status', ['scheduled', 'processing'])
                    ->orWhere(function ($query): void {
                        $query->where('status', 'suppressed')
                            ->where('suppression_reason', 'organiser_invitation_issuing_disabled');
                    });
            })
            ->update([
                'status' => 'cancelled',
                'suppression_reason' => $reason,
                'cancelled_at' => now(),
                'claimed_at' => null,
                'updated_at' => now(),
            ]);
    }

    private function audit(
        ReviewInvitationDelivery $delivery,
        MailgunDeliveryEvent $event,
        string $outcome,
    ): void {
        $organisationId = $delivery->invitation?->performance?->show?->organisation_id;
        if (! $organisationId) {
            return;
        }

        AuditLog::create([
            'organisation_id' => $organisationId,
            'user_id' => null,
            'action' => 'review_invitation.delivery_feedback_received',
            'entity_type' => $delivery->getMorphClass(),
            'entity_id' => $delivery->id,
            'after_state' => [
                'event_type' => $event->event_type,
                'delivery_status' => $delivery->status,
                'outcome' => $outcome,
                'event_at' => $event->event_at->toIso8601String(),
            ],
            'correlation_id' => $delivery->correlation_id,
            'created_at' => now(),
        ]);
    }
}
