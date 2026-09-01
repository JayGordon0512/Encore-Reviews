<?php

namespace Tests\Unit;

use App\Application\Invitations\DetermineInvitationScheduleTime;
use App\Models\Performance;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class InvitationScheduleTimeTest extends TestCase
{
    public function test_it_uses_the_recorded_event_end_plus_the_delivery_delay(): void
    {
        $performance = new Performance([
            'starts_at' => CarbonImmutable::parse('2030-10-10 19:30:00'),
            'ends_at' => CarbonImmutable::parse('2030-10-10 21:00:00'),
        ]);

        $scheduledFor = app(DetermineInvitationScheduleTime::class)->forPerformance($performance, 1);

        $this->assertSame('2030-10-10 22:00', $scheduledFor->format('Y-m-d H:i'));
    }

    public function test_it_uses_the_configured_duration_when_a_provider_has_no_end_time(): void
    {
        config(['encore.invitations.default_event_duration_minutes' => 90]);
        $performance = new Performance([
            'starts_at' => CarbonImmutable::parse('2030-10-10 19:30:00'),
            'ends_at' => null,
        ]);

        $scheduledFor = app(DetermineInvitationScheduleTime::class)->forPerformance($performance, 1);

        $this->assertSame('2030-10-10 22:00', $scheduledFor->format('Y-m-d H:i'));
    }
}
