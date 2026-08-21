<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Performance;
use App\Models\ReviewInvitation;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\Concerns\SignsTicketPalRequests;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;
    use SignsTicketPalRequests;

    public function test_ticketpal_show_upsert_creates_and_updates_show(): void
    {
        Config::set('encore.ticketpal.secret', 'test-secret');
        $organisation = Organisation::create([
            'name' => 'TicketPal Theatre',
            'is_active' => true,
        ]);

        $payload = [
            'provider_event_id' => 'ticketpal-123',
            'title' => 'Test Show',
            'ticket_url' => 'https://tickets.example.com/test-show',
            'status' => 'upcoming',
            'organisation_id' => $organisation->id,
        ];

        $response = $this->postTicketPalJson('/api/ticketpal/shows/upsert', $payload, 'show-create');

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'created' => true,
                'show' => [
                    'title' => 'Test Show',
                    'provider_source' => 'ticketpal',
                    'organisation_id' => $organisation->id,
                ],
            ]);

        $showId = $response->json('show.id');
        $this->assertNotEmpty($showId);
        $this->assertDatabaseHas('shows', [
            'id' => $showId,
            'organisation_id' => $organisation->id,
        ]);

        $response = $this->postTicketPalJson(
            '/api/ticketpal/shows/upsert',
            array_merge($payload, ['title' => 'Updated Title']),
            'show-update'
        );

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

        $invitationResponse = $this->postTicketPalJson('/api/ticketpal/invitations', [
            'performance_id' => $performance->id,
            'email' => 'reviewer@example.com',
        ], 'invitation-create');

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

        $reuseResponse = $this->postJson('/api/reviews', [
            'invitation_token' => $token,
            'email' => 'reviewer@example.com',
            'rating' => 4,
            'would_recommend' => true,
            'content' => 'Trying to submit twice.',
        ]);

        $reuseResponse->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'message' => 'Invalid or expired invitation token.',
            ]);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_review_submission_rejects_email_that_does_not_match_invitation(): void
    {
        Config::set('encore.ticketpal.secret', 'test-secret');

        $show = Show::create([
            'title' => 'Email Match Show',
            'ticket_url' => 'https://tickets.example.com/email-match',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'event-email-match',
            'status' => 'upcoming',
            'slug' => 'email-match-show',
        ]);

        $performance = Performance::create([
            'show_id' => $show->id,
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'perf-email-match',
            'provider_performance_id' => 'perf-email-match',
            'status' => 'scheduled',
        ]);

        $invitationResponse = $this->postTicketPalJson('/api/ticketpal/invitations', [
            'performance_id' => $performance->id,
            'email' => 'invited@example.com',
        ], 'invitation-email-match');

        $token = $invitationResponse->json('invitation.token');

        $reviewResponse = $this->postJson('/api/reviews', [
            'invitation_token' => $token,
            'email' => 'different@example.com',
            'rating' => 5,
            'would_recommend' => true,
            'content' => 'This should not be accepted.',
        ]);

        $reviewResponse->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'message' => 'Invitation token does not match this email address.',
            ]);

        $this->assertDatabaseCount('reviews', 0);
        $this->assertNull(ReviewInvitation::query()->where('performance_id', $performance->id)->first()->used_at);
    }

    public function test_review_submission_is_closed_when_an_imported_historical_show_is_locked(): void
    {
        $show = Show::create([
            'title' => 'Locked Historical Show',
            'ticket_url' => 'https://tickets.example.com/locked-history',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'event-locked-history',
            'status' => 'archived',
            'lifecycle_status' => 'archived',
            'reviews_locked' => true,
            'slug' => 'locked-historical-show',
        ]);
        $performance = Performance::create([
            'show_id' => $show->id,
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'event-locked-history',
            'provider_performance_id' => 'perf-locked-history',
            'status' => 'completed',
        ]);
        $token = 'locked-history-token';
        ReviewInvitation::create([
            'performance_id' => $performance->id,
            'email_hash' => hash('sha256', 'reviewer@example.com'),
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        $this->postJson('/api/reviews', [
            'invitation_token' => $token,
            'email' => 'reviewer@example.com',
            'rating' => 5,
            'would_recommend' => true,
        ])->assertUnprocessable()->assertJson([
            'ok' => false,
            'message' => 'Reviews are closed for this historical show.',
        ]);

        $this->assertDatabaseCount('reviews', 0);
        $this->assertNull(ReviewInvitation::query()->firstOrFail()->used_at);
    }
}
