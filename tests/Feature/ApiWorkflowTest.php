<?php

namespace Tests\Feature;

use App\Models\Performance;
use App\Models\ReviewInvitation;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticketpal_show_upsert_creates_and_updates_show(): void
    {
        Config::set('encore.ticketpal.secret', 'test-secret');

        $payload = [
            'provider_event_id' => 'ticketpal-123',
            'title' => 'Test Show',
            'ticket_url' => 'https://tickets.example.com/test-show',
            'status' => 'upcoming',
        ];

        $response = $this->withHeaders([
            'X-TicketPal-Secret' => 'test-secret',
        ])->postJson('/api/ticketpal/shows/upsert', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'created' => true,
                'show' => ['title' => 'Test Show', 'provider_source' => 'ticketpal'],
            ]);

        $showId = $response->json('show.id');
        $this->assertNotEmpty($showId);

        $response = $this->withHeaders([
            'X-TicketPal-Secret' => 'test-secret',
        ])->postJson('/api/ticketpal/shows/upsert', array_merge($payload, ['title' => 'Updated Title']));

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'created' => false,
                'show' => ['title' => 'Updated Title'],
            ]);
    }

    public function test_ticketpal_invitation_creation_and_review_submission(): void
    {
        Config::set('encore.ticketpal.secret', 'test-secret');

        $show = Show::create([
            'title' => 'Audience Review Show',
            'ticket_url' => 'https://tickets.example.com/audience-review',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'event-123',
            'status' => 'upcoming',
            'slug' => 'audience-review-show',
        ]);

        $performance = Performance::create([
            'show_id' => $show->id,
            'venue_id' => null,
            'starts_at' => now()->addDays(1),
            'status' => 'scheduled',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'perf-123',
            'provider_performance_id' => 'perf-123',
        ]);

        $invitationResponse = $this->withHeaders([
            'X-TicketPal-Secret' => 'test-secret',
        ])->postJson('/api/ticketpal/invitations', [
            'performance_id' => $performance->id,
            'email' => 'reviewer@example.com',
        ]);

        $invitationResponse->assertStatus(201)
            ->assertJsonStructure([
                'ok',
                'invitation' => ['id', 'performance_id', 'sent_at', 'expires_at', 'token'],
            ]);

        $token = $invitationResponse->json('invitation.token');
        $this->assertNotEmpty($token);

        $reviewResponse = $this->postJson('/api/reviews', [
            'invitation_token' => $token,
            'email' => 'reviewer@example.com',
            'display_name' => 'Audience Member',
            'rating' => 5,
            'would_recommend' => true,
            'tags' => ['great', 'easy'],
            'content' => 'Fantastic show.',
        ]);

        $reviewResponse->assertStatus(201)
            ->assertJson([
                'ok' => true,
                'review' => ['performance_id' => $performance->id, 'rating' => 5],
            ]);

        $this->assertDatabaseHas('review_invitations', [
            'performance_id' => $performance->id,
        ]);

        $this->assertNotNull(
            ReviewInvitation::query()
                ->where('performance_id', $performance->id)
                ->first()
                ->used_at
        );
    }
}
