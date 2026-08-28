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
        $response = $this->get('/review/submit?token=unknown-token');

        $response->assertNotFound();
        $response->assertSee('Review invitation unavailable');
        $response->assertDontSee('reviewSubmissionForm');
        $response->assertDontSee('unknown-token');
    }

    public function test_valid_emailed_invitation_link_renders_the_review_form(): void
    {
        $token = 'show-name-token';
        $this->createInvitation($token);

        $response = $this->get('/review/submit?token='.$token);

        $response->assertOk();
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertSee('<meta name="referrer" content="no-referrer">', false);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertSee('You’re reviewing');
        $response->assertSee('Review Target Show');
        $response->assertSee(
            'type="hidden" name="invitation_token" value="'.$token.'"',
            false,
        );
        $response->assertDontSee('Enter your invitation token');
        $response->assertDontSee('<label for="invitation_token">', false);
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
            $response = $this->get('/review/submit?token='.$token);

            $response->assertNotFound();
            $response->assertSee('Review invitation unavailable');
            $response->assertDontSee('reviewSubmissionForm');
        }
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
