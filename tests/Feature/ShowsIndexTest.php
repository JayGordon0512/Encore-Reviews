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
        Show::create([
            'title' => 'Encore Sample Show',
            'slug' => 'encore-sample-show',
            'summary' => 'A test show listing.',
            'ticket_url' => 'https://tickets.example.com/sample-show',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'sample-event-1',
            'status' => 'upcoming',
        ]);

        $response = $this->get('/shows');

        $response->assertStatus(200);
        $response->assertSee('Encore Sample Show');
        $response->assertSee('View show');
    }
}
