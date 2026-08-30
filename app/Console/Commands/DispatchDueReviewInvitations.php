<?php

namespace App\Console\Commands;

use App\Jobs\IssueReviewInvitation;
use App\Models\ReviewInvitationSchedule;
use Illuminate\Console\Command;

class DispatchDueReviewInvitations extends Command
{
    protected $signature = 'encore:invitations:dispatch-due {--limit=100}';

    protected $description = 'Dispatch identifier-only jobs for due Encore review invitations';

    public function handle(): int
    {
        $enabledSources = array_keys(array_filter([
            'provider_v2' => (bool) config('encore.provider_v2.invitation_issuing_enabled'),
            'organiser_csv' => (bool) config('encore.audience_imports.invitation_issuing_enabled'),
        ]));
        if ($enabledSources === []) {
            $this->components->info('Invitation issuing is disabled; no jobs were dispatched.');

            return self::SUCCESS;
        }

        $staleBefore = now()->subMinutes((int) config('encore.invitations.claim_timeout_minutes'));
        $limit = max(1, min((int) $this->option('limit'), 1000));
        $scheduleIds = ReviewInvitationSchedule::query()
            ->whereIn('source', $enabledSources)
            ->where(function ($query) use ($staleBefore): void {
                $query->where(function ($query): void {
                    $query->where('status', 'scheduled')->where('scheduled_for', '<=', now());
                })->orWhere(function ($query) use ($staleBefore): void {
                    $query->where('status', 'processing')->where('claimed_at', '<=', $staleBefore);
                });
            })
            ->orderBy('scheduled_for')
            ->limit($limit)
            ->pluck('id');

        foreach ($scheduleIds as $scheduleId) {
            IssueReviewInvitation::dispatch($scheduleId);
        }

        $this->components->info($scheduleIds->count().' invitation job(s) dispatched.');

        return self::SUCCESS;
    }
}
