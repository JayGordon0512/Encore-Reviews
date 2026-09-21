<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\ReviewInvitationSchedule;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ReleaseHeldProviderInvitations extends Command
{
    protected $signature = 'encore:invitations:release-held-provider
        {--limit=100 : Maximum schedules to release}
        {--commit : Release schedules; without this option the command is a dry run}';

    protected $description = 'Safely release verified provider invitations held while issuing was disabled';

    public function handle(): int
    {
        $limit = max(1, min((int) $this->option('limit'), 1000));
        $count = (clone $this->heldSchedules())->count();
        if (! $this->option('commit')) {
            $this->components->info("Dry run: {$count} verified held provider invitation(s); up to {$limit} would be released.");

            return self::SUCCESS;
        }
        if (! config('encore.provider_v2.invitation_issuing_enabled')) {
            $this->components->error('Provider invitation issuing must be enabled before held invitations can be released.');

            return self::FAILURE;
        }

        $released = DB::transaction(function () use ($limit): int {
            $schedules = $this->heldSchedules()->with('eligibility:id,organisation_id')
                ->lockForUpdate()->orderBy('scheduled_for')->limit($limit)->get();
            foreach ($schedules as $schedule) {
                $correlationId = (string) Str::uuid();
                $schedule->forceFill([
                    'status' => 'scheduled',
                    'suppression_reason' => null,
                    'scheduled_for' => $schedule->scheduled_for->isPast() ? now() : $schedule->scheduled_for,
                    'correlation_id' => $correlationId,
                ])->save();
                AuditLog::create([
                    'organisation_id' => $schedule->eligibility?->organisation_id,
                    'user_id' => null,
                    'action' => 'review_invitation.provider_schedule_released',
                    'entity_type' => $schedule->getMorphClass(),
                    'entity_id' => $schedule->id,
                    'before_state' => ['status' => 'suppressed', 'reason' => 'invitation_issuing_disabled'],
                    'after_state' => ['status' => 'scheduled', 'scheduled_for' => $schedule->scheduled_for->toIso8601String()],
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);
            }

            return $schedules->count();
        });
        $this->components->info("{$released} provider invitation(s) released to the scheduler.");

        return self::SUCCESS;
    }

    private function heldSchedules(): Builder
    {
        return ReviewInvitationSchedule::query()
            ->where('source', 'provider_v2')
            ->where('status', 'suppressed')
            ->where('suppression_reason', 'invitation_issuing_disabled')
            ->whereHas('eligibility', fn ($query) => $query->where('status', 'verified_eligible'));
    }
}
