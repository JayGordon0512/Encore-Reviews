<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\Performance;
use App\Models\Review;
use App\Models\Reviewer;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_requires_authentication(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_customer_can_log_in_to_admin_dashboard(): void
    {
        $organisation = Organisation::create(['name' => 'Customer Theatre', 'is_active' => true]);
        User::factory()->create([
            'organisation_id' => $organisation->id,
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_admin_dashboard_renders_customer_review_overview(): void
    {
        $organisation = Organisation::create(['name' => 'Customer Theatre', 'is_active' => true]);
        $user = User::factory()->create(['organisation_id' => $organisation->id]);
        $show = Show::create([
            'organisation_id' => $organisation->id,
            'title' => 'Admin Visible Show',
            'slug' => 'admin-visible-show',
            'ticket_url' => 'https://tickets.example.com/admin-visible-show',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'admin-visible-event',
            'status' => 'upcoming',
        ]);
        $performance = Performance::create([
            'show_id' => $show->id,
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'admin-visible-event',
            'provider_performance_id' => 'admin-visible-performance',
            'status' => 'scheduled',
        ]);
        $reviewer = Reviewer::create([
            'display_name' => 'Admin Reviewer',
            'email_hash' => hash('sha256', 'admin-reviewer@example.com'),
        ]);

        Review::create([
            'performance_id' => $performance->id,
            'reviewer_id' => $reviewer->id,
            'rating' => 5,
            'would_recommend' => true,
            'content' => 'A dashboard-visible review.',
            'verified' => true,
            'moderation_status' => 'pending',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Review dashboard');
        $response->assertSee('Admin Visible Show');
        $response->assertSee('Pending review queue');
        $response->assertSee('Admin Reviewer');
    }

    public function test_customer_can_approve_a_review_owned_by_their_organisation(): void
    {
        [$user, $review] = $this->createAccountReview('Owned Show');

        $response = $this->actingAs($user)->patch(route('admin.reviews.update', $review), [
            'moderation_status' => 'approved',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'moderation_status' => 'approved',
        ]);

        $auditLog = AuditLog::where('action', 'review.moderated')->sole();
        $this->assertSame($user->id, $auditLog->user_id);
        $this->assertSame($user->organisation_id, $auditLog->organisation_id);
        $this->assertSame([
            'moderation_status' => 'pending',
            'moderation_reason' => null,
        ], $auditLog->before_state);
        $this->assertSame([
            'moderation_status' => 'approved',
            'moderation_reason' => null,
        ], $auditLog->after_state);
    }

    public function test_customer_cannot_moderate_another_organisations_review(): void
    {
        [$owner, $review] = $this->createAccountReview('Private Client Show');
        $otherOrganisation = Organisation::create(['name' => 'Other Organisation', 'is_active' => true]);
        $otherUser = User::factory()->create(['organisation_id' => $otherOrganisation->id]);

        $response = $this->actingAs($otherUser)->patch(route('admin.reviews.update', $review), [
            'moderation_status' => 'approved',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'moderation_status' => 'pending']);
    }

    private function createAccountReview(string $showTitle): array
    {
        $organisation = Organisation::create(['name' => $showTitle.' Organisation', 'is_active' => true]);
        $user = User::factory()->create(['organisation_id' => $organisation->id]);
        $show = Show::create([
            'organisation_id' => $organisation->id,
            'title' => $showTitle,
            'slug' => str($showTitle)->slug(),
            'ticket_url' => 'https://tickets.example.com/'.str($showTitle)->slug(),
            'provider_source' => 'ticketpal',
            'provider_event_id' => str($showTitle)->slug().'-event',
            'status' => 'upcoming',
        ]);
        $performance = Performance::create([
            'show_id' => $show->id,
            'provider_source' => 'ticketpal',
            'provider_event_id' => str($showTitle)->slug().'-event',
            'provider_performance_id' => str($showTitle)->slug().'-performance',
            'status' => 'scheduled',
        ]);
        $reviewer = Reviewer::create([
            'display_name' => 'Moderation Reviewer',
            'email_hash' => hash('sha256', 'moderation@example.com'),
        ]);
        $review = Review::create([
            'performance_id' => $performance->id,
            'reviewer_id' => $reviewer->id,
            'rating' => 4,
            'would_recommend' => true,
            'content' => 'Waiting for moderation.',
            'verified' => true,
            'moderation_status' => 'pending',
            'submitted_at' => now(),
        ]);

        return [$user, $review];
    }
}
