<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\ReviewInvitationSchedule;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReleaseHeldOrganiserInvitations extends Command
{
    protected $signature = 'encore:invitations:release-held-organiser
        {--limit=100 : Maximum schedules to release}
        {--commit : Release the schedules; without this option the command is a dry run}';

    protected $description = 'Safely release organiser invitations held while automatic sending was disabled';

    public function handle(): int
    {
        $limit = max(1, min((int) $this->option('limit'), 1000));
        $query = $this->heldSchedules();
        $heldCount = (clone $query)->count();

        if (! $this->option('commit')) {
            $this->components->info("Dry run: {$heldCount} held organiser invitation(s); up to {$limit} would be released.");

            return self::SUCCESS;
        }

        if (! config('encore.audience_imports.invitation_issuing_enabled')) {
            $this->components->error('Organiser invitation issuing must be enabled before held invitations can be released.');

            return self::FAILURE;
        }

        $released = DB::transaction(function () use ($limit): int {
            $schedules = $this->heldSchedules()
                ->with('audienceAttendance:id,organisation_id')
                ->lockForUpdate()
                ->orderBy('scheduled_for')
                ->limit($limit)
                ->get();

            foreach ($schedules as $schedule) {
                $correlationId = (string) Str::uuid();
                $schedule->forceFill([
                    'status' => 'scheduled',
                    'suppression_reason' => null,
                    'scheduled_for' => $schedule->scheduled_for->isPast() ? now() : $schedule->scheduled_for,
                    'correlation_id' => $correlationId,
                ])->save();

                AuditLog::create([
                    'organisation_id' => $schedule->audienceAttendance?->organisation_id,
                    'user_id' => null,
                    'action' => 'review_invitation.schedule_released',
                    'entity_type' => $schedule->getMorphClass(),
                    'entity_id' => $schedule->id,
                    'before_state' => ['status' => 'suppressed', 'reason' => 'organiser_invitation_issuing_disabled'],
                    'after_state' => ['status' => 'scheduled', 'scheduled_for' => $schedule->scheduled_for->toIso8601String()],
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);
            }

            return $schedules->count();
        });

        $this->components->info("{$released} held organiser invitation(s) released to the scheduler.");

        return self::SUCCESS;
    }

    private function heldSchedules(): Builder
    {
        return ReviewInvitationSchedule::query()
            ->where('source', 'organiser_csv')
            ->where('status', 'suppressed')
            ->where('suppression_reason', 'organiser_invitation_issuing_disabled');
    }
}
