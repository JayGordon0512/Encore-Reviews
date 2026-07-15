<?php

namespace Tests\Feature;

use App\Models\Performance;
use App\Models\Review;
use App\Models\Reviewer;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_renders_with_reviews(): void
    {
        $show = Show::create([
            'title' => 'Test Audience Show',
            'slug' => 'test-audience-show',
            'summary' => 'A great show for testing.',
            'description' => 'This is a public page test for Encore Reviews.',
            'genre' => 'theatre',
            'ticket_url' => 'https://tickets.example.com/test-audience-show',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'event-test-1',
            'status' => 'upcoming',
        ]);

        $performance = Performance::create([
            'show_id' => $show->id,
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'perf-test-1',
            'provider_performance_id' => 'perf-test-1',
            'status' => 'scheduled',
        ]);

        $reviewer = Reviewer::create([
            'display_name' => 'Test Reviewer',
            'email_hash' => hash('sha256', 'reviewer@example.com'),
        ]);

        Review::create([
            'performance_id' => $performance->id,
            'reviewer_id' => $reviewer->id,
            'rating' => 5,
            'would_recommend' => true,
            'content' => 'The show was outstanding.',
            'submitted_at' => now(),
            'verified' => true,
            'moderation_status' => 'approved',
        ]);

        $response = $this->get('/shows/test-audience-show');

        $response->assertStatus(200);
        $response->assertSee('Test Audience Show');
        $response->assertSee('The show was outstanding.');
        $response->assertSee('Encore score 5.0 out of 5');
        $response->assertSee('Review rating 5 out of 5');
        $response->assertDontSee('Rated 5/5');
    }

    public function test_show_page_excludes_pending_reviews_from_public_scores(): void
    {
        $show = Show::create([
            'title' => 'Moderated Audience Show',
            'slug' => 'moderated-audience-show',
            'summary' => 'A show with mixed moderation states.',
            'ticket_url' => 'https://tickets.example.com/moderated-audience-show',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'event-test-2',
            'status' => 'upcoming',
        ]);

        $performance = Performance::create([
            'show_id' => $show->id,
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'perf-test-2',
            'provider_performance_id' => 'perf-test-2',
            'status' => 'scheduled',
        ]);

        $reviewer = Reviewer::create([
            'display_name' => 'Moderated Reviewer',
            'email_hash' => hash('sha256', 'moderated@example.com'),
        ]);

        Review::create([
            'performance_id' => $performance->id,
            'reviewer_id' => $reviewer->id,
            'rating' => 5,
            'would_recommend' => true,
            'content' => 'This approved review is visible.',
            'submitted_at' => now(),
            'verified' => true,
            'moderation_status' => 'approved',
        ]);

        Review::create([
            'performance_id' => $performance->id,
            'reviewer_id' => $reviewer->id,
            'rating' => 1,
            'would_recommend' => false,
            'content' => 'This pending review should stay private.',
            'submitted_at' => now(),
            'verified' => true,
            'moderation_status' => 'pending',
        ]);

        $response = $this->get('/shows/moderated-audience-show');

        $response->assertStatus(200);
        $response->assertSee('This approved review is visible.');
        $response->assertDontSee('This pending review should stay private.');
        $response->assertSee('Encore score 5.0 out of 5');
        $response->assertSee('Based on 1 review.');
        $response->assertSee('Recommend rate: 100%');
    }
}
