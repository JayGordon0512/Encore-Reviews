<?php

namespace App\Jobs;

use App\Application\Invitations\IssueReviewInvitationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IssueReviewInvitation implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public readonly string $scheduleId)
    {
        $this->onQueue('invitations');
    }

    public function handle(IssueReviewInvitationService $service): void
    {
        $service->issue($this->scheduleId);
    }
}
