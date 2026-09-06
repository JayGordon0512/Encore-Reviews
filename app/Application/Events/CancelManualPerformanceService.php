<?php

namespace App\Application\Events;

use App\Models\Performance;
use App\Models\ReviewInvitation;
use App\Models\ReviewInvitationDelivery;
use App\Models\Show;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CancelManualPerformanceService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function cancel(User $actor, Show $show, Performance $performance, ?string $ipAddress, ?string $userAgent): void
    {
        DB::transaction(function () use ($actor, $show, $performance, $ipAddress, $userAgent): void {
            $performance = Performance::query()->lockForUpdate()->findOrFail($performance->id);
            abort_unless($performance->show_id === $show->id && $performance->provider_source === Show::SOURCE_MANUAL, 404);
            if ($performance->status === 'cancelled') {
                return;
            }

            $correlationId = (string) Str::uuid();
            $before = $this->auditLogger->snapshot($performance, ['starts_at', 'ends_at', 'status']);
            $performance->update(['status' => 'cancelled']);
            $scheduleCount = $performance->invitationSchedules()->count();
            $cancelledSchedules = $performance->invitationSchedules()
                ->where(function ($query): void {
                    $query->whereIn('review_invitation_schedules.status', ['scheduled', 'processing'])
                        ->orWhere(function ($query): void {
                            $query->where('review_invitation_schedules.status', 'suppressed')
                                ->where('review_invitation_schedules.suppression_reason', 'organiser_invitation_issuing_disabled');
                        });
                })
                ->update([
                    'status' => 'cancelled',
                    'suppression_reason' => 'performance_cancelled',
                    'cancelled_at' => now(),
                    'claimed_at' => null,
                    'correlation_id' => $correlationId,
                    'updated_at' => now(),
                ]);
            $invitationIds = ReviewInvitation::query()
                ->where('performance_id', $performance->id)
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->pluck('id');
            $revokedInvitations = ReviewInvitation::query()
                ->whereIn('id', $invitationIds)
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                    'revocation_reason' => 'performance_cancelled',
                    'updated_at' => now(),
                ]);
            ReviewInvitationDelivery::query()
                ->whereIn('invitation_id', $invitationIds)
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'error_code' => 'performance_cancelled',
                    'updated_at' => now(),
                ]);

            $this->auditLogger->record(
                $actor,
                'performance.manual_cancelled',
                $performance,
                $show->organisation_id,
                $before,
                [
                    ...$this->auditLogger->snapshot($performance, ['starts_at', 'ends_at', 'status']),
                    'invitation_schedules_cancelled' => $cancelledSchedules,
                    'invitations_revoked' => $revokedInvitations,
                    'schedule_count' => $scheduleCount,
                ],
                $ipAddress,
                $userAgent,
                $correlationId,
            );
        });
    }
}
