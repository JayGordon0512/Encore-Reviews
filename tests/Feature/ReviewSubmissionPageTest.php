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

    public function test_review_submission_page_renders(): void
    {
        $response = $this->get('/review/submit');

        $response->assertStatus(200);
        $response->assertSee('Submit your review');
        $response->assertSee('Invitation token');
        $response->assertSee('Your Encore score');
        $response->assertSee('Would you recommend this show?');
    }

    public function test_review_submission_page_prefills_invitation_token_from_query(): void
    {
        $response = $this->get('/review/submit?token=test-token-123');

        $response->assertStatus(200);
        $response->assertSee('value="test-token-123"', false);
    }

    public function test_review_submission_page_shows_invited_show_name(): void
    {
        $show = Show::create([
            'title' => 'Review Target Show',
            'slug' => 'review-target-show',
            'ticket_url' => 'https://tickets.example.com/review-target-show',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'review-target-event',
            'status' => 'upcoming',
        ]);
        $performance = Performance::create([
            'show_id' => $show->id,
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'review-target-event',
            'provider_performance_id' => 'review-target-performance',
            'starts_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);
        $token = 'show-name-token';

        ReviewInvitation::create([
            'performance_id' => $performance->id,
            'email_hash' => hash('sha256', 'reviewer@example.com'),
            'token_hash' => hash('sha256', $token),
            'sent_at' => now(),
            'expires_at' => now()->addDays(7),
            'provider_source' => 'ticketpal',
        ]);

        $response = $this->get('/review/submit?token='.$token);

        $response->assertStatus(200);
        $response->assertSee('You’re reviewing');
        $response->assertSee('Review Target Show');
    }
}
