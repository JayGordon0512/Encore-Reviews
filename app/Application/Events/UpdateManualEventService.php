<?php

namespace App\Application\Events;

use App\Application\Invitations\DetermineInvitationScheduleTime;
use App\Models\Performance;
use App\Models\ReviewInvitation;
use App\Models\ReviewInvitationDelivery;
use App\Models\Show;
use App\Models\User;
use App\Models\Venue;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class UpdateManualEventService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly DetermineInvitationScheduleTime $scheduleTime,
    ) {}

    /** @param array<string, mixed> $data */
    public function update(
        User $actor,
        Show $show,
        array $data,
        ?string $ipAddress,
        ?string $userAgent,
    ): int {
        return DB::transaction(function () use ($actor, $show, $data, $ipAddress, $userAgent): int {
            $show = Show::query()->lockForUpdate()->findOrFail($show->id);
            $performances = $show->performances()
                ->whereNotIn('status', ['cancelled', 'archived', 'deleted'])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $submittedIds = collect($data['performances'])->pluck('id')->filter()->values();
            if ($submittedIds->diff($performances->keys())->isNotEmpty()
                || $performances->keys()->diff($submittedIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'performances' => 'Every current performance must remain in the form. Cancel a performance from the event page instead.',
                ]);
            }

            $correlationId = (string) Str::uuid();
            $venue = $this->resolveVenue($show, $performances->first()?->venue, $data);
            $before = $this->auditLogger->snapshot($show, ['title', 'summary', 'description', 'genre', 'ticket_url']);
            $show->update([
                'title' => trim($data['title']),
                'summary' => filled($data['summary'] ?? null) ? trim($data['summary']) : null,
                'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
                'genre' => filled($data['genre'] ?? null) ? trim($data['genre']) : null,
                'ticket_url' => $data['ticket_url'] ?? null,
                'ticket_url_source' => filled($data['ticket_url'] ?? null) ? 'organiser' : null,
            ]);

            $rescheduled = 0;
            foreach ($data['performances'] as $performanceData) {
                $startsAt = CarbonImmutable::parse($performanceData['starts_at']);
                $endsAt = $startsAt->addMinutes((int) $data['duration_minutes']);
                $performance = filled($performanceData['id'] ?? null)
                    ? $performances->get($performanceData['id'])
                    : new Performance([
                        'show_id' => $show->id,
                        'status' => 'scheduled',
                        'provider_source' => Show::SOURCE_MANUAL,
                        'provider_event_id' => $show->provider_event_id,
                        'provider_performance_id' => (string) Str::uuid(),
                    ]);
                abort_unless($performance instanceof Performance, 404);

                $timingChanged = ! $performance->exists
                    || ! $performance->starts_at?->equalTo($startsAt)
                    || ! $performance->ends_at?->equalTo($endsAt);
                $performance->fill([
                    'venue_id' => $venue?->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ])->save();

                if ($timingChanged) {
                    $scheduledFor = $this->scheduleTime->forPerformance(
                        $performance,
                        (int) config('encore.audience_imports.invitation_delay_hours'),
                    );
                    if ($scheduledFor->isPast()) {
                        $scheduledFor = now();
                    }
                    $rescheduled += $this->rescheduleUnsentInvitations($performance, $scheduledFor, $correlationId);
                }
            }

            $this->auditLogger->record(
                $actor,
                'event.manual_updated',
                $show,
                $show->organisation_id,
                $before,
                [
                    ...$this->auditLogger->snapshot($show, ['title', 'summary', 'description', 'genre', 'ticket_url']),
                    'duration_minutes' => (int) $data['duration_minutes'],
                    'performance_count' => count($data['performances']),
                    'invitation_schedules_recalculated' => $rescheduled,
                ],
                $ipAddress,
                $userAgent,
                $correlationId,
            );

            return $rescheduled;
        });
    }

    private function rescheduleUnsentInvitations(
        Performance $performance,
        CarbonImmutable $scheduledFor,
        string $correlationId,
    ): int {
        $rescheduled = $performance->invitationSchedules()
            ->where(function ($query): void {
                $query->where('review_invitation_schedules.status', 'scheduled')
                    ->orWhere(function ($query): void {
                        $query->where('review_invitation_schedules.status', 'suppressed')
                            ->where('review_invitation_schedules.suppression_reason', 'organiser_invitation_issuing_disabled');
                    });
            })
            ->update([
                'scheduled_for' => $scheduledFor,
                'correlation_id' => $correlationId,
                'updated_at' => now(),
            ]);

        $processingScheduleIds = $performance->invitationSchedules()
            ->where('review_invitation_schedules.status', 'processing')
            ->pluck('review_invitation_schedules.id');
        if ($processingScheduleIds->isEmpty()) {
            return $rescheduled;
        }

        $invitationIds = ReviewInvitationDelivery::query()
            ->whereIn('schedule_id', $processingScheduleIds)
            ->where('status', 'pending')
            ->pluck('invitation_id');
        ReviewInvitation::query()
            ->whereIn('id', $invitationIds)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'revocation_reason' => 'performance_rescheduled',
                'updated_at' => now(),
            ]);
        ReviewInvitationDelivery::query()
            ->whereIn('invitation_id', $invitationIds)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'error_code' => 'performance_rescheduled',
                'updated_at' => now(),
            ]);

        $issuingEnabled = (bool) config('encore.audience_imports.invitation_issuing_enabled');
        $rescheduled += $performance->invitationSchedules()
            ->whereIn('review_invitation_schedules.id', $processingScheduleIds)
            ->where('review_invitation_schedules.status', 'processing')
            ->update([
                'status' => $issuingEnabled ? 'scheduled' : 'suppressed',
                'suppression_reason' => $issuingEnabled ? null : 'organiser_invitation_issuing_disabled',
                'scheduled_for' => $scheduledFor,
                'claimed_at' => null,
                'correlation_id' => $correlationId,
                'updated_at' => now(),
            ]);

        return $rescheduled;
    }

    /** @param array<string, mixed> $data */
    private function resolveVenue(Show $show, ?Venue $venue, array $data): ?Venue
    {
        if (! filled($data['venue_name'] ?? null)) {
            return null;
        }

        if ($venue === null || $venue->organisation_id !== $show->organisation_id) {
            $venue = new Venue([
                'organisation_id' => $show->organisation_id,
                'slug' => (Str::slug($data['venue_name']) ?: 'venue').'-'.Str::lower(Str::random(8)),
            ]);
        }
        $venue->fill([
            'name' => trim($data['venue_name']),
            'city' => filled($data['venue_city'] ?? null) ? trim($data['venue_city']) : null,
            'postcode' => filled($data['venue_postcode'] ?? null) ? trim($data['venue_postcode']) : null,
        ])->save();

        return $venue;
    }
}
