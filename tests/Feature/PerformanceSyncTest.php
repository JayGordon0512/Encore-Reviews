<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Performance;
use App\Models\Show;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PerformanceSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('encore.ticketpal.secret', 'test-secret');
    }

    public function test_performance_sync_requires_ticketpal_authentication(): void
    {
        $this->postJson('/api/ticketpal/performances/upsert', [])
            ->assertUnauthorized()
            ->assertJson(['ok' => false, 'message' => 'Unauthorized']);
    }

    public function test_performance_sync_rejects_an_unknown_show(): void
    {
        $this->syncPerformance([
            'provider_event_id' => 'missing-event',
            'provider_performance_id' => 'missing-performance',
            'starts_at' => '2026-09-01T19:30:00+01:00',
            'venue_name' => 'Missing Theatre',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('provider_event_id');

        $this->assertDatabaseCount('performances', 0);
        $this->assertDatabaseCount('venues', 0);
    }

    public function test_performance_sync_creates_then_updates_idempotently(): void
    {
        [$organisation, $show] = $this->createOrganisationShow('event-123');
        $payload = [
            'provider_event_id' => 'event-123',
            'provider_performance_id' => 'performance-123',
            'starts_at' => '2026-09-01T19:30:00+01:00',
            'ends_at' => '2026-09-01T21:30:00+01:00',
            'status' => 'scheduled',
            'venue_name' => 'Encore Theatre',
            'venue_city' => 'London',
            'venue_postcode' => 'W1D 6QF',
        ];

        $createResponse = $this->syncPerformance($payload)
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'created' => true,
                'performance' => [
                    'show_id' => $show->id,
                    'status' => 'scheduled',
                ],
            ]);

        $performanceId = $createResponse->json('performance.id');
        $venueId = $createResponse->json('performance.venue_id');

        $updateResponse = $this->syncPerformance(array_merge($payload, [
            'starts_at' => '2026-09-02T20:00:00+01:00',
            'ends_at' => '2026-09-02T22:00:00+01:00',
            'status' => 'on_sale',
            'venue_city' => 'Westminster',
        ]))->assertOk()
            ->assertJson([
                'ok' => true,
                'created' => false,
                'performance' => [
                    'id' => $performanceId,
                    'show_id' => $show->id,
                    'venue_id' => $venueId,
                    'status' => 'on_sale',
                ],
            ]);

        $this->assertSame($performanceId, $updateResponse->json('performance.id'));
        $this->assertDatabaseCount('performances', 1);
        $this->assertDatabaseCount('venues', 1);

        $performance = Performance::findOrFail($performanceId);
        $venue = Venue::findOrFail($venueId);

        $this->assertSame($organisation->id, $venue->organisation_id);
        $this->assertSame('Westminster', $venue->city);
        $this->assertSame('2026-09-02 19:00:00', $performance->starts_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-02 21:00:00', $performance->ends_at->utc()->format('Y-m-d H:i:s'));
        $this->assertNotNull($performance->provider_updated_at);
    }

    public function test_same_venue_slug_is_isolated_by_organisation(): void
    {
        [$firstOrganisation] = $this->createOrganisationShow('first-event');
        [$secondOrganisation] = $this->createOrganisationShow('second-event');

        $firstVenueId = $this->syncPerformance([
            'provider_event_id' => 'first-event',
            'provider_performance_id' => 'first-performance',
            'starts_at' => '2026-10-01T19:00:00Z',
            'venue_name' => 'Shared Hall',
        ])->assertOk()->json('performance.venue_id');

        $secondVenueId = $this->syncPerformance([
            'provider_event_id' => 'second-event',
            'provider_performance_id' => 'second-performance',
            'starts_at' => '2026-10-02T19:00:00Z',
            'venue_name' => 'Shared Hall',
        ])->assertOk()->json('performance.venue_id');

        $this->assertNotSame($firstVenueId, $secondVenueId);
        $this->assertDatabaseHas('venues', ['id' => $firstVenueId, 'organisation_id' => $firstOrganisation->id]);
        $this->assertDatabaseHas('venues', ['id' => $secondVenueId, 'organisation_id' => $secondOrganisation->id]);
    }

    private function syncPerformance(array $payload)
    {
        return $this->withHeader('X-TicketPal-Secret', 'test-secret')
            ->postJson('/api/ticketpal/performances/upsert', $payload);
    }

    private function createOrganisationShow(string $providerEventId): array
    {
        $organisation = Organisation::create([
            'name' => 'Organisation '.$providerEventId,
            'is_active' => true,
        ]);

        $show = Show::create([
            'organisation_id' => $organisation->id,
            'title' => 'Show '.$providerEventId,
            'slug' => 'show-'.$providerEventId,
            'ticket_url' => 'https://tickets.example.com/'.$providerEventId,
            'provider_source' => 'ticketpal',
            'provider_event_id' => $providerEventId,
            'status' => 'upcoming',
        ]);

        return [$organisation, $show];
    }
}
