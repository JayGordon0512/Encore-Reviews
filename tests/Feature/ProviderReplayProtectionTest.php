<?php

namespace Tests\Feature;

use App\Models\IntegrationEvent;
use App\Models\Performance;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\Concerns\SignsTicketPalRequests;
use Tests\TestCase;

class ProviderReplayProtectionTest extends TestCase
{
    use RefreshDatabase;
    use SignsTicketPalRequests;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('encore.ticketpal.secret', 'test-secret');
        Config::set('encore.ticketpal.signature_tolerance_seconds', 300);
    }

    public function test_ticketpal_event_security_headers_are_required(): void
    {
        $this->withHeader('X-TicketPal-Secret', 'test-secret')
            ->postJson('/api/ticketpal/shows/upsert', [])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Missing or invalid TicketPal event security headers.');

        $this->assertDatabaseCount('integration_events', 0);
    }

    public function test_stale_ticketpal_event_is_rejected_before_registration(): void
    {
        $this->postTicketPalJson(
            '/api/ticketpal/shows/upsert',
            [],
            'stale-event',
            now()->subMinutes(6)->timestamp
        )->assertUnauthorized()
            ->assertJsonPath('message', 'TicketPal event timestamp is outside the accepted window.');

        $this->assertDatabaseCount('integration_events', 0);
    }

    public function test_processed_invitation_event_replays_the_encrypted_original_response(): void
    {
        $performance = $this->createPerformance();
        $payload = [
            'performance_id' => $performance->id,
            'email' => 'replay@example.com',
        ];

        $first = $this->postTicketPalJson('/api/ticketpal/invitations', $payload, 'invitation-replay')
            ->assertCreated()
            ->assertHeader('X-Correlation-ID');

        $second = $this->postTicketPalJson('/api/ticketpal/invitations', $payload, 'invitation-replay')
            ->assertCreated()
            ->assertHeader('X-Provider-Event-Replayed', 'true');

        $this->assertSame($first->json(), $second->json());
        $this->assertSame(
            $first->headers->get('X-Correlation-ID'),
            $second->headers->get('X-Correlation-ID')
        );
        $this->assertDatabaseCount('review_invitations', 1);
        $this->assertDatabaseCount('integration_events', 1);

        $event = IntegrationEvent::firstOrFail();
        $this->assertSame('processed', $event->status);
        $this->assertSame(1, $event->attempts);
        $this->assertStringNotContainsString($first->json('invitation.token'), $event->response_body);
    }

    public function test_event_id_reuse_with_a_different_payload_is_rejected(): void
    {
        $payload = [
            'provider_event_id' => 'payload-conflict-show',
            'title' => 'Original title',
            'ticket_url' => 'https://tickets.example.com/original',
        ];

        $this->postTicketPalJson('/api/ticketpal/shows/upsert', $payload, 'payload-conflict')
            ->assertOk();

        $this->postTicketPalJson('/api/ticketpal/shows/upsert', [
            ...$payload,
            'title' => 'Tampered title',
        ], 'payload-conflict')->assertConflict()
            ->assertJsonPath('message', 'TicketPal event ID was reused with a different payload.');

        $this->assertDatabaseCount('integration_events', 1);
        $this->assertSame('Original title', Show::firstOrFail()->title);
    }

    private function createPerformance(): Performance
    {
        $show = Show::create([
            'title' => 'Replay Protected Show',
            'slug' => 'replay-protected-show',
            'ticket_url' => 'https://tickets.example.com/replay-protected-show',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'replay-protected-event',
            'status' => 'upcoming',
        ]);

        return Performance::create([
            'show_id' => $show->id,
            'starts_at' => now()->addDay(),
            'status' => 'scheduled',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'replay-protected-event',
            'provider_performance_id' => 'replay-protected-performance',
        ]);
    }
}
