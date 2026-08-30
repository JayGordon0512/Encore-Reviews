<?php

namespace Tests\Feature;

use App\Models\Performance;
use App\Models\ReviewInvitation;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSubmissionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_navigation_does_not_offer_review_submission(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Submit review');
        $response->assertDontSee(route('review.submit'));
    }

    public function test_review_submission_page_requires_an_invitation_link(): void
    {
        $response = $this->get('/review/submit');

        $response->assertNotFound();
        $response->assertSee('Review invitation unavailable');
        $response->assertSee('personal link in an Encore review invitation email');
        $response->assertDontSee('reviewSubmissionForm');
    }

    public function test_unknown_invitation_link_uses_the_same_unavailable_response(): void
    {
        $response = $this->postJson('/review/invitation/exchange', [
            'invitation_token' => 'unknown-token',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'This review invitation is unavailable.');
        $this->assertStringNotContainsString('unknown-token', $response->getContent());
    }

    public function test_valid_emailed_invitation_is_exchanged_for_a_session_before_rendering_the_form(): void
    {
        $token = 'show-name-token';
        $invitation = $this->createInvitation($token);

        $entry = $this->get('/review/invitation');
        $entry->assertOk();
        $entry->assertHeader('Referrer-Policy', 'no-referrer');
        $entry->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $entry->assertSee('Opening your secure invitation');
        $entry->assertDontSee($token);

        $exchange = $this->postJson('/review/invitation/exchange', [
            'invitation_token' => $token,
        ]);
        $exchange->assertOk()->assertJson([
            'ok' => true,
            'redirect' => route('review.submit'),
        ]);
        $exchange->assertSessionHas('review_invitation.id', $invitation->id);
        $this->assertStringNotContainsString($token, json_encode(session()->all(), JSON_THROW_ON_ERROR));

        $response = $this->get('/review/submit');

        $response->assertOk();
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertSee('<meta name="referrer" content="no-referrer">', false);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertSee('You’re reviewing');
        $response->assertSee('Review Target Show');
        $response->assertDontSee($token);
        $response->assertDontSee('name="invitation_token"', false);
        $response->assertDontSee('Enter your invitation token');
        $response->assertDontSee('<label for="invitation_token">', false);
    }

    public function test_query_string_tokens_are_not_accepted_or_reflected(): void
    {
        $token = 'legacy-query-token';
        $this->createInvitation($token);

        $response = $this->get('/review/submit?token='.$token);

        $response->assertNotFound();
        $response->assertDontSee($token);
        $response->assertDontSee('reviewSubmissionForm');
    }

    public function test_expired_used_and_unsent_invitation_links_are_unavailable(): void
    {
        $this->createInvitation('expired-token', [
            'expires_at' => now()->subMinute(),
        ]);
        $this->createInvitation('used-token', [
            'used_at' => now(),
        ]);
        $this->createInvitation('unsent-token', [
            'sent_at' => null,
        ]);

        foreach (['expired-token', 'used-token', 'unsent-token'] as $token) {
            $response = $this->postJson('/review/invitation/exchange', [
                'invitation_token' => $token,
            ]);

            $response->assertUnprocessable();
            $response->assertJsonPath('message', 'This review invitation is unavailable.');
        }
    }

    public function test_session_bound_review_submission_consumes_invitation_and_session(): void
    {
        $token = 'session-submission-token';
        $invitation = $this->createInvitation($token);

        $this->postJson('/review/invitation/exchange', [
            'invitation_token' => $token,
        ])->assertOk();

        $this->postJson(route('review.submit.store'), [
            'email' => 'reviewer@example.com',
            'display_name' => 'Audience Member',
            'rating' => 5,
            'would_recommend' => true,
            'tags' => ['Moving'],
            'content' => 'A memorable performance.',
        ])->assertCreated()->assertSessionMissing('review_invitation.id');

        $this->assertDatabaseCount('reviews', 1);
        $this->assertNotNull($invitation->fresh()->used_at);
        $this->get('/review/submit')->assertNotFound();
    }

    public function test_review_submission_without_a_successful_exchange_is_rejected(): void
    {
        $this->postJson(route('review.submit.store'), [
            'email' => 'reviewer@example.com',
            'rating' => 5,
            'would_recommend' => true,
        ])->assertUnprocessable()->assertJson([
            'ok' => false,
            'message' => 'Invalid or expired invitation.',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_unsent_invitation_cannot_be_used_through_the_review_api(): void
    {
        $token = 'api-unsent-token';
        $this->createInvitation($token, ['sent_at' => null]);

        $response = $this->postJson('/api/reviews', [
            'invitation_token' => $token,
            'email' => 'reviewer@example.com',
            'rating' => 5,
            'would_recommend' => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Invalid or expired invitation token.');
        $this->assertDatabaseCount('reviews', 0);
    }

    private function createInvitation(string $token, array $overrides = []): ReviewInvitation
    {
        $suffix = substr(hash('sha256', $token), 0, 12);
        $show = Show::create([
            'title' => 'Review Target Show',
            'slug' => 'review-target-show-'.$suffix,
            'ticket_url' => 'https://tickets.example.com/review-target-show',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'review-target-event-'.$suffix,
            'status' => 'upcoming',
        ]);
        $performance = Performance::create([
            'show_id' => $show->id,
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'review-target-event-'.$suffix,
            'provider_performance_id' => 'review-target-performance-'.$suffix,
            'starts_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        return ReviewInvitation::create(array_merge([
            'performance_id' => $performance->id,
            'email_hash' => hash('sha256', 'reviewer@example.com'),
            'token_hash' => hash('sha256', $token),
            'sent_at' => now(),
            'expires_at' => now()->addDays(7),
            'provider_source' => 'ticketpal',
        ], $overrides));
    }
}
