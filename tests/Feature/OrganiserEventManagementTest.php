<?php

namespace Tests\Feature;

use App\Models\AudienceImport;
use App\Models\AuditLog;
use App\Models\Organisation;
use App\Models\Performance;
use App\Models\ProtectedReviewerContact;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
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
            'performances' => [
                ['starts_at' => '2030-10-10T19:30', 'ends_at' => '2030-10-10T21:30'],
                ['starts_at' => '2030-10-11T14:30', 'ends_at' => '2030-10-11T16:30'],
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
        $this->assertDatabaseHas('venues', [
            'organisation_id' => $organisation->id,
            'name' => 'Riverside Hall',
            'city' => 'Glasgow',
        ]);

        $audit = AuditLog::where('action', 'event.manual_created')->sole();
        $this->assertSame($user->id, $audit->user_id);
        $this->assertSame($organisation->id, $audit->organisation_id);
        $this->assertSame(2, $audit->after_state['performance_count']);
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

    public function test_organiser_can_upload_tenant_scoped_artwork_when_creating_an_event(): void
    {
        Storage::fake('public');
        [$organisation, $user] = $this->organiser('Artwork Organisation');

        $response = $this->actingAs($user)->post(route('admin.events.store'), [
            'title' => 'Illustrated Event',
            'event_image' => UploadedFile::fake()->image('poster.jpg', 1200, 675),
            'performances' => [
                ['starts_at' => '2032-06-10T19:30', 'ends_at' => ''],
            ],
        ]);

        $show = Show::where('title', 'Illustrated Event')->firstOrFail();
        $response->assertRedirect(route('admin.events.show', $show));
        $this->assertSame('public', $show->primary_image_disk);
        $this->assertStringStartsWith('event-artwork/'.$organisation->id.'/', $show->primary_image_storage_path);
        $this->assertStringEndsWith('.jpg', $show->primary_image_storage_path);
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
        $this->assertDatabaseHas('audience_attendances', [
            'organisation_id' => $organisation->id,
            'show_id' => $show->id,
            'performance_id' => $performance->id,
            'source' => 'organiser_csv',
            'attendance_state' => 'organiser_confirmed',
        ]);

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
