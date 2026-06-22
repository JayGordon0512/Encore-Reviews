<?php

namespace Tests\Feature;

use App\Models\Performance;
use App\Models\Review;
use App\Models\Reviewer;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_index_renders_available_shows(): void
    {
        $this->createShow();

        $response = $this->get('/shows');

        $response->assertStatus(200);
        $response->assertSee('Encore Sample Show');
        $response->assertSee('View show');
    }

    public function test_home_page_renders_available_shows(): void
    {
        $this->createShow();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Encore Sample Show');
        $response->assertSee('View show');
    }

    public function test_featured_show_cards_render_approved_review_score(): void
    {
        $show = $this->createShow();
        $performance = Performance::create([
            'show_id' => $show->id,
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'sample-performance-1',
            'provider_performance_id' => 'sample-performance-1',
            'status' => 'scheduled',
        ]);
        $reviewer = Reviewer::create([
            'display_name' => 'Score Reviewer',
            'email_hash' => hash('sha256', 'score@example.com'),
        ]);

        Review::create([
            'performance_id' => $performance->id,
            'reviewer_id' => $reviewer->id,
            'rating' => 5,
            'would_recommend' => true,
            'verified' => true,
            'moderation_status' => 'approved',
            'submitted_at' => now(),
        ]);

        Review::create([
            'performance_id' => $performance->id,
            'reviewer_id' => $reviewer->id,
            'rating' => 1,
            'would_recommend' => false,
            'verified' => true,
            'moderation_status' => 'pending',
            'submitted_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('5.0');
        $response->assertSee('/5 · 1 review');
        $response->assertDontSee('/5 · 2 reviews');
    }

    public function test_about_page_renders_previous_marketing_content(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('What TicketPal organisers get');
        $response->assertSee('How it works');
    }

    private function createShow(): Show
    {
        return Show::create([
            'title' => 'Encore Sample Show',
            'slug' => 'encore-sample-show',
            'summary' => 'A test show listing.',
            'ticket_url' => 'https://tickets.example.com/sample-show',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'sample-event-1',
            'status' => 'upcoming',
        ]);
    }
}
