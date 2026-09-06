<?php

namespace Tests\Feature;

use App\Application\Invitations\IssueReviewInvitationService;
use App\Jobs\IssueReviewInvitation;
use App\Models\AudienceImport;
use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\Performance;
use App\Models\ProtectedReviewerContact;
use App\Models\ReviewInvitation;
use App\Models\ReviewInvitationDelivery;
use App\Models\ReviewInvitationSchedule;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganiserEventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'encore.audience_imports.contact_fingerprint_key' => 'organiser-import-test-key',
            'encore.audience_imports.contact_fingerprint_version' => 1,
            'encore.audience_imports.max_rows' => 1000,
            'encore.audience_imports.invitation_issuing_enabled' => false,
            'encore.audience_imports.invitation_delay_hours' => 1,
            'encore.invitations.default_event_duration_minutes' => 150,
            'encore.event_images.disk' => 'public',
            'encore.event_images.max_size_kb' => 5120,
        ]);
    }

    public function test_organiser_can_create_an_independent_event_with_multiple_dates(): void
    {
        [$organisation, $user] = $this->organiser('Independent Arts');

        $response = $this->actingAs($user)->post(route('admin.events.store'), [
            'title' => 'Independent Festival',
            'summary' => 'A provider-neutral live event.',
            'description' => 'Created directly by the organiser.',
            'genre' => 'Theatre',
            'ticket_url' => '',
            'venue_name' => 'Riverside Hall',
            'venue_city' => 'Glasgow',
            'venue_postcode' => 'G1 1AA',
            'duration_minutes' => 120,
            'performances' => [
                ['starts_at' => '2030-10-10T19:30'],
                ['starts_at' => '2030-10-11T14:30'],
            ],
        ]);

        $show = Show::where('title', 'Independent Festival')->firstOrFail();
        $response->assertRedirect(route('admin.events.show', $show));
        $this->assertSame($organisation->id, $show->organisation_id);
        $this->assertSame(Show::SOURCE_MANUAL, $show->provider_source);
        $this->assertNull($show->ticket_url);
        $this->assertSame('active', $show->lifecycle_status);
        $this->assertCount(2, $show->performances);
        $this->assertSame(
            ['2030-10-10 19:30', '2030-10-11 14:30'],
            $show->performances()->orderBy('starts_at')->get()->map(fn (Performance $performance) => $performance->starts_at->format('Y-m-d H:i'))->all(),
        );
        $this->assertSame(
            ['2030-10-10 21:30', '2030-10-11 16:30'],
            $show->performances()->orderBy('starts_at')->get()->map(fn (Performance $performance) => $performance->ends_at->format('Y-m-d H:i'))->all(),
        );
        $this->assertDatabaseHas('venues', [
            'organisation_id' => $organisation->id,
            'name' => 'Riverside Hall',
            'city' => 'Glasgow',
        ]);

        $audit = AuditLog::where('action', 'event.manual_created')->sole();
        $this->assertSame($user->id, $audit->user_id);
        $this->assertSame($organisation->id, $audit->organisation_id);
        $this->assertSame(2, $audit->after_state['performance_count']);
        $this->assertSame(120, $audit->after_state['duration_minutes']);
    }

    public function test_manual_event_is_visible_on_dashboard_and_public_page_with_dates(): void
    {
        [, $user, $show] = $this->manualEvent('Community Concert');
        $show->performances()->create([
            'starts_at' => '2031-05-02 20:00:00',
            'status' => 'scheduled',
            'provider_source' => Show::SOURCE_MANUAL,
            'provider_event_id' => $show->provider_event_id,
            'provider_performance_id' => 'manual-performance-two',
        ]);

        $dashboard = $this->actingAs($user)->get(route('admin.dashboard'));
        $dashboard->assertOk();
        $dashboard->assertSee('Create event');
        $dashboard->assertSee('Manage');
        $dashboard->assertSee('2 date(s)');

        $public = $this->get(route('shows.show', $show));
        $public->assertOk();
        $public->assertSee('Event dates');
        $public->assertSee('Organiser supplied');
        $public->assertSee('Fri 2 May 2031, 20:00');
        $public->assertSee('assets/encore-event-placeholder.svg', false);
        $public->assertDontSee('powered by verified TicketPal ticket data');
    }

    public function test_manual_event_requires_a_realistic_duration(): void
    {
        [, $user] = $this->organiser('Duration Validation');

        $this->actingAs($user)->post(route('admin.events.store'), [
            'title' => 'Missing Duration Event',
            'performances' => [['starts_at' => '2030-10-10T19:30']],
        ])->assertSessionHasErrors('duration_minutes');

        $this->actingAs($user)->post(route('admin.events.store'), [
            'title' => 'Unrealistic Duration Event',
            'duration_minutes' => 5,
            'performances' => [['starts_at' => '2030-10-10T19:30']],
        ])->assertSessionHasErrors('duration_minutes');

        $this->assertDatabaseCount('shows', 0);
    }

    public function test_organiser_can_upload_tenant_scoped_artwork_when_creating_an_event(): void
    {
        Storage::fake('public');
        [$organisation, $user] = $this->organiser('Artwork Organisation');

        $response = $this->actingAs($user)->post(route('admin.events.store'), [
            'title' => 'Illustrated Event',
            'event_image' => UploadedFile::fake()->image('poster.jpg', 1200, 675),
            'duration_minutes' => 90,
            'performances' => [
                ['starts_at' => '2032-06-10T19:30'],
            ],
        ]);

        $show = Show::where('title', 'Illustrated Event')->firstOrFail();
        $response->assertRedirect(route('admin.events.show', $show));
        $this->assertSame('public', $show->primary_image_disk);
        $this->assertStringStartsWith('event-artwork/'.$organisation->id.'/', $show->primary_image_storage_path);
        $this->assertStringEndsWith('.jpg', $show->primary_image_storage_path);
        $this->assertSame('2032-06-10 21:00', $show->performances()->sole()->ends_at->format('Y-m-d H:i'));
        Storage::disk('public')->assertExists($show->primary_image_storage_path);

        $public = $this->get(route('shows.show', $show));
        $public->assertOk()->assertSee($show->primary_image_path, false);
        $this->assertTrue(AuditLog::where('action', 'event.manual_created')->sole()->after_state['has_custom_artwork']);
    }

    public function test_organiser_can_replace_artwork_and_other_tenants_cannot(): void
    {
        Storage::fake('public');
        [, $owner, $show] = $this->manualEvent('Replaceable Artwork');
        Storage::disk('public')->put('event-artwork/'.$show->organisation_id.'/old.jpg', 'old artwork');
        $show->update([
            'primary_image_path' => '/storage/event-artwork/'.$show->organisation_id.'/old.jpg',
            'primary_image_disk' => 'public',
            'primary_image_storage_path' => 'event-artwork/'.$show->organisation_id.'/old.jpg',
        ]);
        [, $otherUser] = $this->organiser('Artwork Intruder');

        $this->actingAs($otherUser)->patch(route('admin.events.artwork.update', $show), [
            'event_image' => UploadedFile::fake()->image('intruder.jpg', 1200, 675),
        ])->assertNotFound();

        $response = $this->actingAs($owner)->patch(route('admin.events.artwork.update', $show), [
            'event_image' => UploadedFile::fake()->image('replacement.png', 1200, 675),
        ]);

        $response->assertRedirect()->assertSessionHas('status', 'Event artwork updated.');
        $show->refresh();
        Storage::disk('public')->assertMissing('event-artwork/'.$show->organisation_id.'/old.jpg');
        Storage::disk('public')->assertExists($show->primary_image_storage_path);
        $this->assertStringEndsWith('.png', $show->primary_image_storage_path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'event.manual_artwork_updated',
            'organisation_id' => $show->organisation_id,
        ]);
    }

    public function test_event_artwork_rejects_unsafe_or_too_small_files(): void
    {
        [, $user, $show] = $this->manualEvent('Validated Artwork');

        $this->actingAs($user)->from(route('admin.events.show', $show))
            ->patch(route('admin.events.artwork.update', $show), [
                'event_image' => UploadedFile::fake()->createWithContent('poster.svg', '<svg></svg>'),
            ])->assertSessionHasErrors('event_image');

        $this->actingAs($user)->from(route('admin.events.show', $show))
            ->patch(route('admin.events.artwork.update', $show), [
                'event_image' => UploadedFile::fake()->image('tiny.jpg', 200, 200),
            ])->assertSessionHasErrors('event_image');
    }

    public function test_organiser_can_import_encrypted_deduplicated_customers_for_one_date(): void
    {
        [$organisation, $user, $show, $performance] = $this->manualEvent('Audience Import Event');
        $csv = UploadedFile::fake()->createWithContent('customers.csv', implode("\n", [
            'name,email',
            'Alice Audience,ALICE@example.com',
            'Duplicate Alice,alice@example.com',
            'Invalid Customer,not-an-email',
            'Bob Audience,bob@example.com',
        ]));

        $response = $this->actingAs($user)->post(route('admin.audience-imports.store', $show), [
            'performance_id' => $performance->id,
            'customers_csv' => $csv,
            'attendance_confirmed' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Imported 2 customer(s); skipped 2 invalid or duplicate row(s).');
        $audienceImport = AudienceImport::sole();
        $this->assertSame($organisation->id, $audienceImport->organisation_id);
        $this->assertSame(4, $audienceImport->rows_total);
        $this->assertSame(2, $audienceImport->rows_imported);
        $this->assertSame(2, $audienceImport->rows_skipped);
        $this->assertSame('completed', $audienceImport->status);
        $this->assertDatabaseCount('protected_reviewer_contacts', 2);
        $this->assertDatabaseCount('audience_attendances', 2);
        $this->assertDatabaseCount('review_invitation_schedules', 2);
        $this->assertSame(
            ['suppressed'],
            ReviewInvitationSchedule::query()->distinct()->pluck('status')->all(),
        );
        $this->assertSame(
            ['organiser_csv'],
            ReviewInvitationSchedule::query()->distinct()->pluck('source')->all(),
        );
        $this->assertSame(
            ['2031-05-01 23:00', '2031-05-01 23:00'],
            ReviewInvitationSchedule::query()->orderBy('id')->get()
                ->map(fn (ReviewInvitationSchedule $schedule) => $schedule->scheduled_for->format('Y-m-d H:i'))->all(),
        );
        $this->assertDatabaseHas('audience_attendances', [
            'organisation_id' => $organisation->id,
            'show_id' => $show->id,
            'performance_id' => $performance->id,
            'source' => 'organiser_csv',
            'attendance_state' => 'organiser_confirmed',
        ]);

        $managementPage = $this->actingAs($user)->get(route('admin.events.show', $show));
        $managementPage->assertOk();
        $managementPage->assertSee('Automatic review invitations are paused.');
        $managementPage->assertSeeText('2 held');
        $managementPage->assertSee('Invitations will be held while automatic sending is paused.');

        $decryptedEmails = ProtectedReviewerContact::all()
            ->map(fn (ProtectedReviewerContact $contact): string => Crypt::decryptString($contact->email_ciphertext))
            ->sort()->values()->all();
        $this->assertSame(['alice@example.com', 'bob@example.com'], $decryptedEmails);
        foreach (ProtectedReviewerContact::all() as $contact) {
            $this->assertStringNotContainsString('@example.com', $contact->getRawOriginal('email_ciphertext'));
        }

        $audit = AuditLog::where('action', 'audience.csv_imported')->sole();
        $this->assertSame(2, $audit->after_state['rows_imported']);
        $this->assertArrayNotHasKey('email', $audit->after_state);
        $this->assertStringNotContainsString('alice@example.com', json_encode($audit->after_state));
    }

    public function test_organiser_attendance_can_issue_an_invitation_while_provider_issuing_is_disabled(): void
    {
        [, $user, $show, $performance] = $this->manualEvent('Independent Invitation Event');
        $performance->update([
            'starts_at' => now()->subHours(4),
            'ends_at' => now()->subHours(2),
            'status' => 'completed',
        ]);
        config([
            'app.url' => 'https://staging.encorereviews.co.uk',
            'encore.provider_v2.invitation_issuing_enabled' => false,
            'encore.audience_imports.invitation_issuing_enabled' => true,
            'encore.invitations.token_digest_key' => 'manual-invitation-test-key',
        ]);
        Mail::fake();
        Queue::fake();

        $this->actingAs($user)->post(route('admin.audience-imports.store', $show), [
            'performance_id' => $performance->id,
            'customers_csv' => UploadedFile::fake()->createWithContent(
                'customers.csv',
                "name,email\nAlex Audience,alex@example.com",
            ),
            'attendance_confirmed' => '1',
        ])->assertRedirect();

        $schedule = ReviewInvitationSchedule::sole();
        $this->assertSame('organiser_csv', $schedule->source);
        $this->assertSame('scheduled', $schedule->status);
        $this->assertNull($schedule->eligibility_id);
        $this->assertNotNull($schedule->audience_attendance_id);

        $this->artisan('encore:invitations:dispatch-due')->assertSuccessful();
        Queue::assertPushed(IssueReviewInvitation::class, fn (IssueReviewInvitation $job): bool => $job->scheduleId === $schedule->id);

        (new IssueReviewInvitation($schedule->id))
            ->handle($this->app->make(IssueReviewInvitationService::class));

        Mail::assertSentCount(1);
        $invitation = ReviewInvitation::sole();
        $this->assertNull($invitation->eligibility_id);
        $this->assertSame($schedule->audience_attendance_id, $invitation->audience_attendance_id);
        $this->assertSame('organiser_csv', $invitation->provider_source);
        $this->assertSame('organiser_confirmed', $invitation->attendance_state);
        $this->assertSame('sent', $invitation->status);
        $this->assertDatabaseHas('review_invitation_schedules', [
            'id' => $schedule->id,
            'status' => 'issued',
        ]);

        $managementPage = $this->actingAs($user)->get(route('admin.events.show', $show));
        $managementPage->assertOk();
        $managementPage->assertSee('Automatic review invitations are active.');
        $managementPage->assertSeeText('1 sent');
        $managementPage->assertDontSee('alex@example.com');
    }

    public function test_held_organiser_invitations_require_an_explicit_audited_release(): void
    {
        [$organisation, $user, $show, $performance] = $this->manualEvent('Held Invitation Event');
        $performance->update([
            'starts_at' => now()->subHours(4),
            'ends_at' => now()->subHours(2),
            'status' => 'completed',
        ]);

        $this->actingAs($user)->post(route('admin.audience-imports.store', $show), [
            'performance_id' => $performance->id,
            'customers_csv' => UploadedFile::fake()->createWithContent(
                'customers.csv',
                "name,email\nHeld Audience,held@example.com",
            ),
            'attendance_confirmed' => '1',
        ])->assertRedirect();

        $schedule = ReviewInvitationSchedule::sole();
        $this->assertSame('suppressed', $schedule->status);

        $this->artisan('encore:invitations:release-held-organiser')
            ->expectsOutputToContain('Dry run: 1 held organiser invitation(s)')
            ->assertSuccessful();
        $this->assertSame('suppressed', $schedule->fresh()->status);

        $this->artisan('encore:invitations:release-held-organiser', ['--commit' => true])
            ->assertFailed();
        $this->assertSame('suppressed', $schedule->fresh()->status);

        config(['encore.audience_imports.invitation_issuing_enabled' => true]);
        $this->artisan('encore:invitations:release-held-organiser', ['--commit' => true])
            ->expectsOutputToContain('1 held organiser invitation(s) released')
            ->assertSuccessful();

        $schedule->refresh();
        $this->assertSame('scheduled', $schedule->status);
        $this->assertNull($schedule->suppression_reason);
        $this->assertTrue($schedule->scheduled_for->isToday());
        $this->assertDatabaseHas('audit_logs', [
            'organisation_id' => $organisation->id,
            'user_id' => null,
            'action' => 'review_invitation.schedule_released',
            'entity_id' => $schedule->id,
        ]);
    }

    public function test_organiser_can_edit_event_timing_and_unsent_schedules_are_recalculated(): void
    {
        [$organisation, $user, $show, $performance] = $this->manualEvent('Rescheduled Event');
        $this->actingAs($user)->post(route('admin.audience-imports.store', $show), [
            'performance_id' => $performance->id,
            'customers_csv' => UploadedFile::fake()->createWithContent('customers.csv', "email\nreschedule@example.com"),
            'attendance_confirmed' => '1',
        ])->assertRedirect();

        $this->actingAs($user)->get(route('admin.events.edit', $show))
            ->assertOk()
            ->assertSee('Timing changes automatically recalculate review emails');

        $response = $this->actingAs($user)->patch(route('admin.events.update', $show), [
            'title' => 'Rescheduled Event Updated',
            'summary' => 'Updated details',
            'description' => '',
            'genre' => 'Comedy',
            'ticket_url' => 'https://example.com/rescheduled',
            'venue_name' => 'Updated Hall',
            'venue_city' => 'Leeds',
            'venue_postcode' => 'LS1 1AA',
            'duration_minutes' => 180,
            'performances' => [
                ['id' => $performance->id, 'starts_at' => '2031-05-02T20:00'],
                ['starts_at' => '2031-05-03T14:00'],
            ],
        ]);

        $response->assertRedirect(route('admin.events.show', $show));
        $response->assertSessionHas('status', 'Event updated; 1 unsent invitation schedule(s) recalculated.');
        $show->refresh();
        $performance->refresh();
        $this->assertSame('Rescheduled Event Updated', $show->title);
        $this->assertSame('2031-05-02 20:00', $performance->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2031-05-02 23:00', $performance->ends_at->format('Y-m-d H:i'));
        $this->assertSame('Updated Hall', $performance->venue->name);
        $this->assertCount(2, $show->performances);
        $schedule = ReviewInvitationSchedule::sole();
        $this->assertSame('suppressed', $schedule->status);
        $this->assertSame('2031-05-03 00:00', $schedule->scheduled_for->format('Y-m-d H:i'));

        $audit = AuditLog::where('action', 'event.manual_updated')->sole();
        $this->assertSame($organisation->id, $audit->organisation_id);
        $this->assertSame(1, $audit->after_state['invitation_schedules_recalculated']);
    }

    public function test_rescheduling_requeues_an_in_flight_invitation_without_sending_the_old_link(): void
    {
        [, $user, $show, $performance] = $this->manualEvent('In Flight Reschedule');
        $this->actingAs($user)->post(route('admin.audience-imports.store', $show), [
            'performance_id' => $performance->id,
            'customers_csv' => UploadedFile::fake()->createWithContent('customers.csv', "email\ninflight@example.com"),
            'attendance_confirmed' => '1',
        ])->assertRedirect();
        $schedule = ReviewInvitationSchedule::sole();
        $schedule->update(['status' => 'processing', 'claimed_at' => now()]);
        $invitation = ReviewInvitation::create([
            'audience_attendance_id' => $schedule->audience_attendance_id,
            'performance_id' => $performance->id,
            'email_hash' => hash('sha256', 'inflight@example.com'),
            'token_hash' => hash('sha256', 'inflight-token'),
            'status' => 'issued',
            'expires_at' => now()->addWeek(),
            'provider_source' => 'organiser_csv',
            'attendance_state' => 'organiser_confirmed',
        ]);
        $delivery = ReviewInvitationDelivery::create([
            'invitation_id' => $invitation->id,
            'schedule_id' => $schedule->id,
            'correlation_id' => (string) str()->uuid(),
            'channel' => 'email',
            'status' => 'pending',
            'attempted_at' => now(),
        ]);

        $this->actingAs($user)->patch(route('admin.events.update', $show), [
            'title' => $show->title,
            'duration_minutes' => 120,
            'performances' => [
                ['id' => $performance->id, 'starts_at' => '2031-05-04T18:00'],
            ],
        ])->assertSessionHas('status', 'Event updated; 1 unsent invitation schedule(s) recalculated.');

        $schedule->refresh();
        $this->assertSame('suppressed', $schedule->status);
        $this->assertSame('organiser_invitation_issuing_disabled', $schedule->suppression_reason);
        $this->assertSame('2031-05-04 21:00', $schedule->scheduled_for->format('Y-m-d H:i'));
        $this->assertNull($schedule->claimed_at);
        $this->assertSame('revoked', $invitation->fresh()->status);
        $this->assertSame('performance_rescheduled', $invitation->fresh()->revocation_reason);
        $this->assertSame('failed', $delivery->fresh()->status);
        $this->assertSame('performance_rescheduled', $delivery->fresh()->error_code);
    }

    public function test_rescheduling_does_not_rewrite_an_issued_invitation_schedule(): void
    {
        [, $user, $show, $performance] = $this->manualEvent('Sent Reschedule');
        $this->actingAs($user)->post(route('admin.audience-imports.store', $show), [
            'performance_id' => $performance->id,
            'customers_csv' => UploadedFile::fake()->createWithContent('customers.csv', "email\nsent@example.com"),
            'attendance_confirmed' => '1',
        ])->assertRedirect();
        $schedule = ReviewInvitationSchedule::sole();
        $originalScheduledFor = $schedule->scheduled_for;
        $schedule->update(['status' => 'issued', 'issued_at' => now()]);

        $this->actingAs($user)->patch(route('admin.events.update', $show), [
            'title' => $show->title,
            'duration_minutes' => 180,
            'performances' => [
                ['id' => $performance->id, 'starts_at' => '2031-05-05T20:00'],
            ],
        ])->assertSessionHas('status', 'Event updated; 0 unsent invitation schedule(s) recalculated.');

        $schedule->refresh();
        $this->assertSame('issued', $schedule->status);
        $this->assertTrue($schedule->scheduled_for->equalTo($originalScheduledFor));
    }

    public function test_cancelling_a_performance_withdraws_pending_and_unused_invitations(): void
    {
        [, $user, $show, $performance] = $this->manualEvent('Cancelled Performance Event');
        $this->actingAs($user)->post(route('admin.audience-imports.store', $show), [
            'performance_id' => $performance->id,
            'customers_csv' => UploadedFile::fake()->createWithContent('customers.csv', "email\ncancelled@example.com"),
            'attendance_confirmed' => '1',
        ])->assertRedirect();
        $schedule = ReviewInvitationSchedule::sole();
        $attendance = $schedule->audienceAttendance;
        $invitation = ReviewInvitation::create([
            'audience_attendance_id' => $attendance->id,
            'performance_id' => $performance->id,
            'email_hash' => hash('sha256', 'cancelled@example.com'),
            'token_hash' => hash('sha256', 'unused-cancelled-token'),
            'status' => 'sent',
            'sent_at' => now(),
            'expires_at' => now()->addWeek(),
            'provider_source' => 'organiser_csv',
            'attendance_state' => 'organiser_confirmed',
        ]);

        $this->actingAs($user)->patch(route('admin.events.performances.cancel', [$show, $performance]))
            ->assertRedirect(route('admin.events.show', $show))
            ->assertSessionHas('status', 'Performance cancelled and its unused invitations withdrawn.');

        $this->assertSame('cancelled', $performance->fresh()->status);
        $this->assertSame('cancelled', $schedule->fresh()->status);
        $this->assertSame('performance_cancelled', $schedule->fresh()->suppression_reason);
        $this->assertSame('revoked', $invitation->fresh()->status);
        $this->assertSame('performance_cancelled', $invitation->fresh()->revocation_reason);
        $this->assertDatabaseHas('audit_logs', ['action' => 'performance.manual_cancelled']);

        $managementPage = $this->actingAs($user)->get(route('admin.events.show', $show));
        $managementPage->assertOk()->assertSee('Cancelled');
        $managementPage->assertDontSee('value="'.$performance->id.'"', false);

        $publicPage = $this->get(route('shows.show', $show));
        $publicPage->assertOk()->assertDontSee('Thu 1 May 2031, 19:30');
    }

    public function test_customers_cannot_be_imported_for_a_cancelled_performance(): void
    {
        [, $user, $show, $performance] = $this->manualEvent('Cancelled Import Event');
        $performance->update(['status' => 'cancelled']);

        $this->actingAs($user)->post(route('admin.audience-imports.store', $show), [
            'performance_id' => $performance->id,
            'customers_csv' => UploadedFile::fake()->createWithContent('customers.csv', "email\nblocked@example.com"),
            'attendance_confirmed' => '1',
        ])->assertNotFound();

        $this->assertDatabaseCount('audience_imports', 0);
        $this->assertDatabaseCount('audience_attendances', 0);
    }

    public function test_other_tenants_cannot_edit_or_cancel_a_manual_event(): void
    {
        [, , $show, $performance] = $this->manualEvent('Protected Editable Event');
        [, $otherUser] = $this->organiser('Other Editing Organisation');

        $this->actingAs($otherUser)->get(route('admin.events.edit', $show))->assertNotFound();
        $this->actingAs($otherUser)->patch(route('admin.events.update', $show), [])->assertNotFound();
        $this->actingAs($otherUser)->patch(route('admin.events.performances.cancel', [$show, $performance]))->assertNotFound();
    }

    public function test_import_requires_attendance_confirmation_and_email_header(): void
    {
        [, $user, $show, $performance] = $this->manualEvent('Validated Import');
        $csv = UploadedFile::fake()->createWithContent('customers.csv', "name,phone\nAlex,01234");

        $response = $this->actingAs($user)->from(route('admin.events.show', $show))
            ->post(route('admin.audience-imports.store', $show), [
                'performance_id' => $performance->id,
                'customers_csv' => $csv,
            ]);

        $response->assertRedirect(route('admin.events.show', $show));
        $response->assertSessionHasErrors(['attendance_confirmed']);
        $this->assertDatabaseCount('audience_imports', 0);

        $response = $this->actingAs($user)->from(route('admin.events.show', $show))
            ->post(route('admin.audience-imports.store', $show), [
                'performance_id' => $performance->id,
                'customers_csv' => UploadedFile::fake()->createWithContent('customers.csv', "name,phone\nAlex,01234"),
                'attendance_confirmed' => '1',
            ]);

        $response->assertSessionHasErrors(['customers_csv']);
        $this->assertDatabaseCount('audience_imports', 0);
    }

    public function test_event_and_customer_imports_are_tenant_isolated(): void
    {
        [, $owner, $show, $performance] = $this->manualEvent('Private Organiser Event');
        [, $otherUser, $otherShow, $otherPerformance] = $this->manualEvent('Other Organiser Event');

        $this->actingAs($otherUser)->get(route('admin.events.show', $show))->assertNotFound();
        $this->actingAs($otherUser)->post(route('admin.audience-imports.store', $show), [
            'performance_id' => $performance->id,
            'customers_csv' => UploadedFile::fake()->createWithContent('customers.csv', "email\nother@example.com"),
            'attendance_confirmed' => '1',
        ])->assertNotFound();

        $this->actingAs($owner)->post(route('admin.audience-imports.store', $show), [
            'performance_id' => $otherPerformance->id,
            'customers_csv' => UploadedFile::fake()->createWithContent('customers.csv', "email\nowner@example.com"),
            'attendance_confirmed' => '1',
        ])->assertNotFound();

        $this->assertNotSame($show->organisation_id, $otherShow->organisation_id);
        $this->assertDatabaseCount('audience_imports', 0);
        $this->assertDatabaseCount('audience_attendances', 0);
    }

    public function test_ticketpal_events_cannot_be_reclassified_through_manual_import(): void
    {
        [$organisation, $user] = $this->organiser('TicketPal Organisation');
        $show = Show::create([
            'organisation_id' => $organisation->id,
            'title' => 'TicketPal Event',
            'slug' => 'ticketpal-event',
            'ticket_url' => 'https://ticketpal.example/event',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'tp-event',
            'status' => 'upcoming',
        ]);

        $this->actingAs($user)->get(route('admin.events.show', $show))->assertForbidden();
    }

    public function test_customer_import_template_is_available_to_organisers(): void
    {
        [, $user] = $this->organiser('Template Organisation');

        $response = $this->actingAs($user)->get(route('admin.audience-imports.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertSee("name,email\nAlex Morgan,alex@example.com");
    }

    /** @return array{Organisation, User} */
    private function organiser(string $name): array
    {
        $organisation = Organisation::create(['name' => $name, 'is_active' => true]);
        $user = User::factory()->create([
            'organisation_id' => $organisation->id,
            'role' => 'customer_admin',
            'is_active' => true,
        ]);

        return [$organisation, $user];
    }

    /** @return array{Organisation, User, Show, Performance} */
    private function manualEvent(string $title): array
    {
        [$organisation, $user] = $this->organiser($title.' Organisation');
        $reference = str($title)->slug().'-event';
        $show = Show::create([
            'organisation_id' => $organisation->id,
            'title' => $title,
            'slug' => str($title)->slug(),
            'ticket_url' => null,
            'provider_source' => Show::SOURCE_MANUAL,
            'provider_event_id' => $reference,
            'status' => 'upcoming',
            'lifecycle_status' => 'active',
        ]);
        $performance = Performance::create([
            'show_id' => $show->id,
            'starts_at' => '2031-05-01 19:30:00',
            'status' => 'scheduled',
            'provider_source' => Show::SOURCE_MANUAL,
            'provider_event_id' => $reference,
            'provider_performance_id' => $reference.'-one',
        ]);

        return [$organisation, $user, $show, $performance];
    }
}
