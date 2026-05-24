<?php

namespace Tests\Feature;

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
    }
}
