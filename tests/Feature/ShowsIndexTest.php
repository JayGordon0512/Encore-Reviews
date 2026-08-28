<?php

namespace Tests\Feature;

use App\Models\Performance;
use App\Models\Review;
use App\Models\Reviewer;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    public function test_show_cards_render_the_ticketpal_event_image(): void
    {
        $show = $this->createShow();
        $show->update([
            'primary_image_path' => 'https://staging.ticketpal.co.uk/storage/35/conversions/event-display.jpg',
        ]);

        foreach (['/', '/shows'] as $path) {
            $response = $this->get($path);

            $response->assertOk();
            $response->assertSee($show->primary_image_path, false);
            $response->assertSee('Encore Sample Show event artwork');
        }
    }

    public function test_shows_can_be_searched_by_title_summary_or_genre(): void
    {
        $this->createShow([
            'title' => 'Dear Evan Hansen',
            'summary' => 'A contemporary musical.',
            'genre' => 'Musical theatre',
        ]);
        $this->createShow([
            'title' => 'The Comedy Hour',
            'summary' => 'An evening of stand-up.',
            'genre' => 'Comedy',
        ]);

        $response = $this->get('/shows?q=musical');

        $response->assertOk();
        $response->assertSee('Dear Evan Hansen');
        $response->assertDontSee('The Comedy Hour');
        $response->assertSee('1 show found');
    }

    public function test_shows_can_be_filtered_by_status_and_archived_shows_stay_hidden(): void
    {
        $this->createShow(['title' => 'Playing Tonight', 'status' => 'now_playing']);
        $this->createShow(['title' => 'Opening Soon', 'status' => 'upcoming']);
        $this->createShow(['title' => 'Closed Production', 'status' => 'archived']);

        $response = $this->get('/shows?status=now_playing');

        $response->assertOk();
        $response->assertSee('Playing Tonight');
        $response->assertDontSee('Opening Soon');
        $response->assertDontSee('Closed Production');
    }

    public function test_show_results_are_paginated_and_keep_active_filters(): void
    {
        foreach (range(1, 13) as $number) {
            $this->createShow([
                'title' => sprintf('Musical %02d', $number),
                'genre' => 'Musical',
            ]);
        }

        $response = $this->get('/shows?q=musical');

        $response->assertOk();
        $response->assertSee('13 shows found');
        $response->assertSee('Page 1 of 2');
        $response->assertSee('/shows?q=musical&amp;page=2', false);
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
        $response->assertSee('Encore score 5.0 out of 5 from 1 review');
        $response->assertSee('1 review');
        $response->assertDontSee('5.0 /5');
        $response->assertDontSee('/5 · 2 reviews');
    }

    public function test_organiser_benefits_page_renders_sales_content(): void
    {
        $response = $this->get('/organisers');

        $response->assertOk();
        $response->assertSee('Turn real audience experience into lasting trust');
        $response->assertSee('Reviews people can trust');
        $response->assertSee('Clear ownership protects organisers and audiences');
        $response->assertSee('Create organiser account');
    }

    public function test_public_navigation_links_to_the_organiser_benefits_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('For organisers');
        $response->assertSee(route('organisers'));
        $response->assertSee('Create account');
        $response->assertSee(route('organisers.create'));
        $response->assertDontSee('>About<', false);
    }

    private function createShow(array $attributes = []): Show
    {
        $identifier = Str::lower(Str::random(12));

        return Show::create(array_merge([
            'title' => 'Encore Sample Show',
            'slug' => 'encore-sample-show-'.$identifier,
            'summary' => 'A test show listing.',
            'ticket_url' => 'https://tickets.example.com/sample-show',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'sample-event-'.$identifier,
            'status' => 'upcoming',
        ], $attributes));
    }
}
