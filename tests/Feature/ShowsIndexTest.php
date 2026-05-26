<?php

namespace Tests\Feature;

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
