<?php

namespace App\Application\Invitations;

use App\Models\Performance;
use Carbon\CarbonImmutable;

final class DetermineInvitationScheduleTime
{
    public function forPerformance(Performance $performance, int $delayHours): CarbonImmutable
    {
        $startsAt = $performance->starts_at === null
            ? CarbonImmutable::now()
            : CarbonImmutable::instance($performance->starts_at);
        $endsAt = $performance->ends_at === null
            ? $startsAt->addMinutes((int) config('encore.invitations.default_event_duration_minutes', 150))
            : CarbonImmutable::instance($performance->ends_at);

        return $endsAt->addHours($delayHours);
    }
}
