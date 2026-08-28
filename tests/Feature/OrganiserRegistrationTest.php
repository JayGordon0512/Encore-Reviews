<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrganiserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_has_been_removed(): void
    {
        $this->get('/about')->assertNotFound();
    }

    public function test_account_page_offers_new_registration_and_ticketpal_login(): void
    {
        config(['encore.ticketpal.organiser_login_url' => 'https://staging.ticketpal.co.uk/login']);

        $response = $this->get(route('organisers.create'));

        $response->assertOk();
        $response->assertSee('Create your organiser account');
        $response->assertSee('Already a TicketPal organiser?');
        $response->assertSee('Use your TicketPal details to log in.');
        $response->assertSee('Log in with TicketPal');
        $response->assertSee('https://staging.ticketpal.co.uk/login', false);
        $response->assertSee(route('organisers.store'), false);
    }

    public function test_new_organiser_registration_creates_an_audited_pending_account(): void
    {
        $response = $this->post(route('organisers.store'), [
            'organisation_name' => 'New Stage Company',
            'name' => 'Alex Organiser',
            'email' => 'ALEX@EXAMPLE.TEST',
            'password' => 'secure-stage-2026',
            'password_confirmation' => 'secure-stage-2026',
            'authority_confirmed' => '1',
        ]);

        $organisation = Organisation::where('name', 'New Stage Company')->firstOrFail();
        $user = User::where('email', 'alex@example.test')->firstOrFail();
        $response->assertRedirect(route('organisers.create'));
        $response->assertSessionHas('status');
        $this->assertFalse($organisation->is_active);
        $this->assertSame('pending_review', $organisation->lifecycle_status);
        $this->assertFalse($user->is_active);
        $this->assertSame($organisation->id, $user->organisation_id);
        $this->assertTrue(Hash::check('secure-stage-2026', $user->password));
        $this->assertDatabaseHas('organisation_user_memberships', [
            'organisation_id' => $organisation->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => false,
        ]);

        $audit = AuditLog::sole();
        $this->assertSame('organisation.registration_submitted', $audit->action);
        $this->assertNull($audit->user_id);
        $this->assertArrayNotHasKey('password', $audit->after_state);
        $this->assertGuest();
    }

    public function test_registration_rejects_duplicate_email_and_unconfirmed_authority(): void
    {
        User::factory()->create(['email' => 'existing@example.test']);

        $response = $this->from(route('organisers.create'))->post(route('organisers.store'), [
            'organisation_name' => 'Duplicate Company',
            'name' => 'Existing Person',
            'email' => 'EXISTING@EXAMPLE.TEST',
            'password' => 'secure-stage-2026',
            'password_confirmation' => 'secure-stage-2026',
        ]);

        $response->assertRedirect(route('organisers.create'));
        $response->assertSessionHasErrors(['email', 'authority_confirmed']);
        $this->assertDatabaseMissing('organisations', ['name' => 'Duplicate Company']);
        $this->assertDatabaseCount('audit_logs', 0);
    }
}
