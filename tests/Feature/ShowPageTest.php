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
        ]);

        $response = $this->get('/shows/test-audience-show');

        $response->assertStatus(200);
        $response->assertSee('Test Audience Show');
        $response->assertSee('The show was outstanding.');
        $response->assertSee('5/5');
    }
}
