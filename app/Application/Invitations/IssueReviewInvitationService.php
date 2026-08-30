<?php

namespace App\Application\Invitations;

use App\Contracts\ReviewInvitationSender;
use App\Domain\Invitations\InvitationToken;
use App\Models\AudienceAttendance;
use App\Models\AuditLog;
use App\Models\IntegrationProvider;
use App\Models\Organisation;
use App\Models\OutboxMessage;
use App\Models\Performance;
use App\Models\ProtectedReviewerContact;
use App\Models\ReviewEligibility;
use App\Models\ReviewInvitation;
use App\Models\ReviewInvitationDelivery;
use App\Models\ReviewInvitationSchedule;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class IssueReviewInvitationService
{
    public function __construct(
        private readonly InvitationToken $tokens,
        private readonly ReviewInvitationSender $sender,
    ) {}

    public function issue(string $scheduleId): void
    {
        $delivery = $this->claimAndCreate($scheduleId);
        if ($delivery === null) {
            return;
        }

        if (! $this->isStillDeliverable($delivery['schedule_id'], $delivery['invitation_id'])) {
            return;
        }

        try {
            $this->sender->send(
                $delivery['email'],
                $delivery['display_name'],
                $delivery['show_title'],
                $delivery['review_url'],
                $delivery['expires_at'],
            );
        } catch (Throwable) {
            $this->markFailed($delivery['schedule_id'], $delivery['invitation_id']);

            return;
        }

        $this->markSent($delivery['schedule_id'], $delivery['invitation_id']);
    }

    /** @return array<string, mixed>|null */
    private function claimAndCreate(string $scheduleId): ?array
    {
        return DB::transaction(function () use ($scheduleId): ?array {
            $schedule = ReviewInvitationSchedule::query()->lockForUpdate()->find($scheduleId);
            if (! $schedule || in_array($schedule->status, ['issued', 'cancelled', 'suppressed', 'dead_lettered'], true)) {
                return null;
            }
            if (! $this->sourceEnabled($schedule->source)) {
                return null;
            }

            $staleBefore = now()->subMinutes((int) config('encore.invitations.claim_timeout_minutes'));
            if ($schedule->status === 'processing' && $schedule->claimed_at?->isAfter($staleBefore)) {
                return null;
            }
            if ($schedule->scheduled_for->isFuture()) {
                return null;
            }
            if ($schedule->attempts >= (int) config('encore.invitations.max_attempts')) {
                $schedule->forceFill(['status' => 'dead_lettered', 'dead_lettered_at' => now()])->save();

                return null;
            }

            $authority = $this->resolveAuthority($schedule, true);
            if ($authority === null) {
                $this->suppress($schedule, 'eligibility_unavailable');

                return null;
            }

            $contact = ProtectedReviewerContact::find($authority['contact_id']);
            $organisation = Organisation::find($authority['organisation_id']);
            $performance = Performance::query()->with('show')->find($authority['performance_id']);
            if (! $contact || $contact->status !== 'active') {
                $this->suppress($schedule, 'contact_suppressed');

                return null;
            }
            if (! $organisation?->is_active) {
                $this->suppress($schedule, 'organisation_inactive');

                return null;
            }
            if (! $performance || in_array($performance->status, ['cancelled', 'archived', 'deleted'], true)
                || ! $performance->show || $performance->show->reviews_locked) {
                $this->suppress($schedule, 'performance_unavailable');

                return null;
            }
            if (ReviewInvitation::query()
                ->where($authority['invitation_key'], $authority['authority_id'])
                ->whereNotNull('used_at')->exists()) {
                $this->suppress($schedule, 'eligibility_redeemed');

                return null;
            }

            $this->revokeIncompleteInvitation($authority['invitation_key'], $authority['authority_id']);

            $token = $this->tokens->create();
            $expiresAt = now()->addDays((int) config('encore.invitations.expiry_days'));
            $email = Crypt::decryptString($contact->email_ciphertext);
            $displayName = Crypt::decryptString($contact->display_name_ciphertext);
            $provider = $authority['provider_id']
                ? IntegrationProvider::find($authority['provider_id'])
                : null;
            $correlationId = $schedule->correlation_id ?: (string) Str::uuid();
            $invitation = ReviewInvitation::create([
                'eligibility_id' => $authority['eligibility_id'],
                'audience_attendance_id' => $authority['audience_attendance_id'],
                'performance_id' => $authority['performance_id'],
                'email_hash' => hash('sha256', Str::lower(trim($email))),
                'token_hash' => $this->tokens->digest($token),
                'token_version' => InvitationToken::VERSION,
                'status' => 'issued',
                'expires_at' => $expiresAt,
                'provider_source' => $provider?->slug ?? $schedule->source,
                'provider_booking_id' => $authority['provider_booking_id'],
                'attendance_state' => $authority['attendance_state'],
                'meta' => ['purpose' => $authority['purpose'], 'authority_source' => $schedule->source],
            ]);
            ReviewInvitationDelivery::create([
                'invitation_id' => $invitation->id,
                'schedule_id' => $schedule->id,
                'correlation_id' => $correlationId,
                'channel' => 'email',
                'status' => 'pending',
                'attempted_at' => now(),
            ]);
            $schedule->forceFill([
                'correlation_id' => $correlationId,
                'status' => 'processing',
                'attempts' => $schedule->attempts + 1,
                'claimed_at' => now(),
                'last_error_code' => null,
            ])->save();

            OutboxMessage::create([
                'event_type' => 'ReviewInvitationIssued',
                'aggregate_type' => 'ReviewInvitation',
                'aggregate_id' => $invitation->id,
                'organisation_id' => $authority['organisation_id'],
                'provider_id' => $authority['provider_id'],
                'payload_version' => 1,
                'payload' => array_filter([
                    'invitation_id' => $invitation->id,
                    'eligibility_id' => $authority['eligibility_id'],
                    'audience_attendance_id' => $authority['audience_attendance_id'],
                ]),
                'correlation_id' => $correlationId,
                'occurred_at' => now(),
                'available_at' => now(),
            ]);
            $this->audit($authority['organisation_id'], 'review_invitation.issued', $invitation->id, $correlationId);

            return [
                'schedule_id' => $schedule->id,
                'invitation_id' => $invitation->id,
                'email' => $email,
                'display_name' => $displayName,
                'show_title' => $performance->show->title,
                'review_url' => route('review.invitation').'#token='.rawurlencode($token),
                'expires_at' => $expiresAt,
            ];
        });
    }

    private function isStillDeliverable(string $scheduleId, string $invitationId): bool
    {
        return DB::transaction(function () use ($scheduleId, $invitationId): bool {
            $schedule = ReviewInvitationSchedule::query()->lockForUpdate()->find($scheduleId);
            $invitation = ReviewInvitation::query()->lockForUpdate()->find($invitationId);
            $authority = $schedule ? $this->resolveAuthority($schedule, true) : null;
            $contact = $authority ? ProtectedReviewerContact::find($authority['contact_id']) : null;
            $organisation = $authority ? Organisation::find($authority['organisation_id']) : null;
            $performance = $authority
                ? Performance::query()->with('show')->find($authority['performance_id'])
                : null;

            if ($schedule?->status === 'processing' && $invitation?->revoked_at === null
                && $this->sourceEnabled($schedule->source) && $authority
                && $contact?->status === 'active'
                && $organisation?->is_active && $performance
                && ! in_array($performance->status, ['cancelled', 'archived', 'deleted'], true)
                && $performance->show && ! $performance->show->reviews_locked) {
                return true;
            }

            if ($invitation && $invitation->revoked_at === null) {
                $invitation->forceFill([
                    'status' => 'revoked', 'revoked_at' => now(),
                    'revocation_reason' => 'eligibility_unavailable',
                ])->save();
            }
            if ($schedule && $schedule->status === 'processing') {
                $schedule->forceFill([
                    'status' => 'cancelled', 'cancelled_at' => now(),
                    'suppression_reason' => 'eligibility_unavailable',
                ])->save();
            }

            return false;
        });
    }

    private function markSent(string $scheduleId, string $invitationId): void
    {
        DB::transaction(function () use ($scheduleId, $invitationId): void {
            $schedule = ReviewInvitationSchedule::query()->lockForUpdate()->findOrFail($scheduleId);
            $invitation = ReviewInvitation::query()->lockForUpdate()->findOrFail($invitationId);
            if ($schedule->status !== 'processing' || $invitation->revoked_at !== null) {
                return;
            }

            $invitation->forceFill(['status' => 'sent', 'sent_at' => now()])->save();
            ReviewInvitationDelivery::query()->where('invitation_id', $invitationId)->update([
                'status' => 'sent', 'sent_at' => now(), 'error_code' => null, 'updated_at' => now(),
            ]);
            $schedule->forceFill([
                'status' => 'issued', 'issued_at' => now(), 'claimed_at' => null,
                'last_error_code' => null,
            ])->save();
            $authority = $this->resolveAuthority($schedule);
            if ($authority) {
                $this->audit($authority['organisation_id'], 'review_invitation.sent', $invitationId, $schedule->correlation_id);
            }
        });
    }

    private function markFailed(string $scheduleId, string $invitationId): void
    {
        DB::transaction(function () use ($scheduleId, $invitationId): void {
            $schedule = ReviewInvitationSchedule::query()->lockForUpdate()->findOrFail($scheduleId);
            $invitation = ReviewInvitation::query()->lockForUpdate()->findOrFail($invitationId);
            $invitation->forceFill([
                'status' => 'revoked', 'revoked_at' => now(),
                'revocation_reason' => 'delivery_failed',
            ])->save();
            ReviewInvitationDelivery::query()->where('invitation_id', $invitationId)->update([
                'status' => 'failed', 'error_code' => 'mail_transport_failure', 'updated_at' => now(),
            ]);

            $deadLettered = $schedule->attempts >= (int) config('encore.invitations.max_attempts');
            $schedule->forceFill([
                'status' => $deadLettered ? 'dead_lettered' : 'scheduled',
                'scheduled_for' => $deadLettered
                    ? $schedule->scheduled_for
                    : now()->addMinutes((int) config('encore.invitations.retry_delay_minutes')),
                'claimed_at' => null,
                'dead_lettered_at' => $deadLettered ? now() : null,
                'last_error_code' => 'mail_transport_failure',
            ])->save();
        });
    }

    private function revokeIncompleteInvitation(string $authorityKey, string $authorityId): void
    {
        $invitations = ReviewInvitation::query()
            ->where($authorityKey, $authorityId)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->lockForUpdate()
            ->get();

        foreach ($invitations as $invitation) {
            $invitation->forceFill([
                'status' => 'revoked', 'revoked_at' => now(),
                'revocation_reason' => 'delivery_replaced',
            ])->save();
            ReviewInvitationDelivery::query()->where('invitation_id', $invitation->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed', 'error_code' => 'delivery_replaced', 'updated_at' => now()]);
        }
    }

    private function suppress(ReviewInvitationSchedule $schedule, string $reason): void
    {
        $schedule->forceFill([
            'status' => 'suppressed', 'suppression_reason' => $reason,
            'claimed_at' => null,
        ])->save();
    }

    /** @return array<string, mixed>|null */
    private function resolveAuthority(ReviewInvitationSchedule $schedule, bool $lock = false): ?array
    {
        if ($schedule->source === 'provider_v2' && $schedule->eligibility_id) {
            $query = ReviewEligibility::query();
            if ($lock) {
                $query->lockForUpdate();
            }
            $eligibility = $query->find($schedule->eligibility_id);
            if (! $eligibility || $eligibility->status !== 'eligible') {
                return null;
            }

            return [
                'authority_id' => $eligibility->id,
                'invitation_key' => 'eligibility_id',
                'eligibility_id' => $eligibility->id,
                'audience_attendance_id' => null,
                'organisation_id' => $eligibility->organisation_id,
                'performance_id' => $eligibility->performance_id,
                'contact_id' => $eligibility->contact_id,
                'provider_id' => $eligibility->provider_id,
                'provider_booking_id' => $eligibility->provider_booking_id,
                'attendance_state' => 'eligible',
                'purpose' => $eligibility->purpose,
            ];
        }

        if ($schedule->source === 'organiser_csv' && $schedule->audience_attendance_id) {
            $query = AudienceAttendance::query();
            if ($lock) {
                $query->lockForUpdate();
            }
            $attendance = $query->find($schedule->audience_attendance_id);
            if (! $attendance || $attendance->status !== 'active'
                || $attendance->attendance_state !== 'organiser_confirmed') {
                return null;
            }

            return [
                'authority_id' => $attendance->id,
                'invitation_key' => 'audience_attendance_id',
                'eligibility_id' => null,
                'audience_attendance_id' => $attendance->id,
                'organisation_id' => $attendance->organisation_id,
                'performance_id' => $attendance->performance_id,
                'contact_id' => $attendance->contact_id,
                'provider_id' => null,
                'provider_booking_id' => null,
                'attendance_state' => $attendance->attendance_state,
                'purpose' => 'encore_review',
            ];
        }

        return null;
    }

    private function sourceEnabled(string $source): bool
    {
        return match ($source) {
            'provider_v2' => (bool) config('encore.provider_v2.invitation_issuing_enabled'),
            'organiser_csv' => (bool) config('encore.audience_imports.invitation_issuing_enabled'),
            default => false,
        };
    }

    private function audit(string $organisationId, string $action, string $entityId, string $correlationId): void
    {
        AuditLog::create([
            'organisation_id' => $organisationId,
            'user_id' => null,
            'action' => $action,
            'entity_type' => 'review_invitation',
            'entity_id' => $entityId,
            'correlation_id' => $correlationId,
            'created_at' => now(),
        ]);
    }
}
