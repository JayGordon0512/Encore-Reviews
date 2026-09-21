<?php

namespace App\Application\Invitations;

use App\Models\Performance;
use App\Models\ReviewInvitation;
use App\Models\ReviewInvitationSchedule;

final class ReconcileProviderPerformanceSchedules
{
    public function __construct(private readonly DetermineInvitationScheduleTime $scheduleTime) {}

    public function reconcile(Performance $performance, string $correlationId): void
    {
        $cancelled = in_array($performance->status, ['cancelled', 'archived', 'deleted'], true);
        $scheduledFor = $this->scheduleTime->forPerformance(
            $performance,
            (int) config('encore.provider_v2.invitation_delay_hours'),
        );
        if ($scheduledFor->isPast()) {
            $scheduledFor = now();
        }

        $schedules = ReviewInvitationSchedule::query()
            ->where('source', 'provider_v2')
            ->whereHas('eligibility', fn ($query) => $query->where('performance_id', $performance->id))
            ->whereIn('status', ['scheduled', 'suppressed', 'processing', 'issued'])
            ->lockForUpdate()
            ->get();

        foreach ($schedules as $schedule) {
            if ($cancelled) {
                $this->revokeUnusedInvitation($schedule, 'performance_cancelled');
                $schedule->forceFill([
                    'status' => 'cancelled',
                    'suppression_reason' => 'performance_cancelled',
                    'cancelled_at' => now(),
                    'claimed_at' => null,
                    'correlation_id' => $correlationId,
                ])->save();

                continue;
            }

            // A sent invitation is immutable delivery history. Rescheduling only
            // moves pending work; it must not send a second invitation.
            if ($schedule->status === 'issued') {
                continue;
            }

            if ($schedule->status === 'processing') {
                $this->revokeUnusedInvitation($schedule, 'performance_rescheduled');
            }
            $verified = $schedule->eligibility?->status === 'verified_eligible';
            $issuing = (bool) config('encore.provider_v2.invitation_issuing_enabled');
            $schedule->forceFill([
                'scheduled_for' => $scheduledFor,
                'status' => $verified && $issuing ? 'scheduled' : 'suppressed',
                'suppression_reason' => $verified
                    ? ($issuing ? null : 'invitation_issuing_disabled')
                    : 'attendance_pending',
                'claimed_at' => null,
                'issued_at' => null,
                'cancelled_at' => null,
                'correlation_id' => $correlationId,
            ])->save();
        }
    }

    private function revokeUnusedInvitation(ReviewInvitationSchedule $schedule, string $reason): void
    {
        ReviewInvitation::query()
            ->where('eligibility_id', $schedule->eligibility_id)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'revocation_reason' => $reason,
                'updated_at' => now(),
            ]);
    }
}
