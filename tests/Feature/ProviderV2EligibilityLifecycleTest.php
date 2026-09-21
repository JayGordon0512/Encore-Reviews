<?php

namespace Tests\Feature;

use App\Models\IntegrationCredential;
use App\Models\IntegrationOrganisationMapping;
use App\Models\IntegrationPerformanceMapping;
use App\Models\IntegrationProvider;
use App\Models\IntegrationShowMapping;
use App\Models\Organisation;
use App\Models\Performance;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class ProviderV2EligibilityLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private const KEY_ID = 'ticketpal-lifecycle-test';

    private const SECRET = 'lifecycle-secret-test-only';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'encore.provider_v2.ingress_enabled' => true,
            'encore.provider_v2.invitation_issuing_enabled' => false,
            'encore.provider_v2.contact_fingerprint_key' => 'fingerprint-test-key',
            'encore.provider_v2.secret_references.fixture-lifecycle' => self::SECRET,
        ]);
        $provider = IntegrationProvider::create(['slug' => 'ticketpal', 'name' => 'TicketPal', 'is_active' => true]);
        $credential = IntegrationCredential::create([
            'provider_id' => $provider->id,
            'key_id' => self::KEY_ID,
            'account_reference' => 'ticketpal-test',
            'secret_reference' => 'fixture-lifecycle',
            'operation_scopes' => ['review-eligibility:write', 'review-eligibility-lifecycle:write'],
            'activated_at' => now()->subMinute(),
        ]);
        $organisation = Organisation::create(['name' => 'Lifecycle Theatre', 'is_active' => true]);
        $credential->organisations()->attach($organisation->id);
        $show = Show::create([
            'organisation_id' => $organisation->id,
            'title' => 'Lifecycle Show',
            'slug' => 'lifecycle-show',
            'ticket_url' => 'https://tickets.example.test/lifecycle',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'show-1',
            'status' => 'upcoming',
        ]);
        $performance = Performance::create([
            'show_id' => $show->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'scheduled',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'show-1',
            'provider_performance_id' => 'performance-1',
        ]);
        $organisationMapping = IntegrationOrganisationMapping::create([
            'provider_id' => $provider->id,
            'account_reference' => 'ticketpal-test',
            'external_organisation_id' => 'organisation-1',
            'organisation_id' => $organisation->id,
        ]);
        $showMapping = IntegrationShowMapping::create([
            'organisation_mapping_id' => $organisationMapping->id,
            'provider_id' => $provider->id,
            'account_reference' => 'ticketpal-test',
            'external_show_id' => 'show-1',
            'organisation_id' => $organisation->id,
            'show_id' => $show->id,
        ]);
        IntegrationPerformanceMapping::create([
            'show_mapping_id' => $showMapping->id,
            'provider_id' => $provider->id,
            'account_reference' => 'ticketpal-test',
            'external_performance_id' => 'performance-1',
            'organisation_id' => $organisation->id,
            'show_id' => $show->id,
            'performance_id' => $performance->id,
        ]);
    }

    public function test_candidate_requires_attendance_and_lifecycle_is_idempotent(): void
    {
        $bookingId = 'booking-123';
        $this->signedPost('/api/v2/integrations/review-invitation-eligibilities', [
            'event_id' => (string) Str::uuid(),
            'schema_version' => '2.0',
            'occurred_at' => now()->toIso8601String(),
            'provider' => 'ticketpal',
            'provider_booking_id' => $bookingId,
            'provider_show_id' => 'show-1',
            'provider_performance_id' => 'performance-1',
            'reviewer' => ['name' => 'Test Reviewer', 'email' => 'reviewer@example.test'],
            'admission_quantity' => 2,
            'consent' => [
                'purpose' => 'encore_review',
                'policy_version' => 'ticketpal-encore-r1',
                'captured_at' => now()->subMinute()->toIso8601String(),
            ],
        ], 'eligibility-1')->assertAccepted();
        $this->assertDatabaseHas('review_eligibilities', [
            'provider_booking_id' => $bookingId,
            'status' => 'eligible_pending_attendance',
        ]);
        $this->assertDatabaseHas('review_invitation_schedules', [
            'status' => 'suppressed',
            'suppression_reason' => 'attendance_pending',
        ]);

        $eventId = (string) Str::uuid();
        $payload = [
            'event_id' => $eventId,
            'schema_version' => '2.1',
            'occurred_at' => now()->toIso8601String(),
            'provider' => 'ticketpal',
            'provider_booking_id' => $bookingId,
            'event_type' => 'attendance_confirmed',
        ];
        $this->signedPost('/api/v2/integrations/review-eligibility-lifecycle-events', $payload, 'attendance-1')
            ->assertAccepted()->assertJsonPath('eligibility_status', 'verified_eligible');
        $this->signedPost('/api/v2/integrations/review-eligibility-lifecycle-events', $payload, 'attendance-1')
            ->assertAccepted()->assertJsonPath('status', 'duplicate');
        $this->assertDatabaseCount('review_eligibility_lifecycle_events', 1);
        $this->assertDatabaseHas('review_invitation_schedules', [
            'status' => 'suppressed',
            'suppression_reason' => 'invitation_issuing_disabled',
        ]);

        $this->signedPost('/api/v2/integrations/review-eligibility-lifecycle-events', [
            'event_id' => (string) Str::uuid(),
            'schema_version' => '2.1',
            'occurred_at' => now()->toIso8601String(),
            'provider' => 'ticketpal',
            'provider_booking_id' => $bookingId,
            'event_type' => 'admission_quantity_changed',
            'admission_quantity' => 1,
        ], 'quantity-1')->assertAccepted();
        $this->assertDatabaseHas('review_eligibilities', ['provider_booking_id' => $bookingId, 'admission_quantity' => 1]);

        $this->signedPost('/api/v2/integrations/review-eligibility-lifecycle-events', [
            'event_id' => (string) Str::uuid(),
            'schema_version' => '2.1',
            'occurred_at' => now()->toIso8601String(),
            'provider' => 'ticketpal',
            'provider_booking_id' => $bookingId,
            'event_type' => 'eligibility_revoked',
            'reason' => 'full_refund',
        ], 'revoke-1')->assertAccepted();
        $this->assertDatabaseHas('review_eligibilities', ['provider_booking_id' => $bookingId, 'status' => 'revoked']);
        $this->assertDatabaseHas('review_invitation_schedules', ['status' => 'cancelled', 'suppression_reason' => 'full_refund']);
    }

    /** @param array<string, mixed> $payload */
    private function signedPost(string $path, array $payload, string $idempotencyKey): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $nonce = (string) Str::uuid();
        $correlationId = (string) Str::uuid();
        $digest = hash('sha256', $body);
        $canonical = implode("\n", ['POST', $path, $timestamp, $nonce, $digest]);

        return $this->call('POST', $path, [], [], [], $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'X-Provider-Key-Id' => self::KEY_ID,
            'X-Request-Timestamp' => $timestamp,
            'X-Request-Nonce' => $nonce,
            'X-Request-Signature' => 'v1='.hash_hmac('sha256', $canonical, self::SECRET),
            'Idempotency-Key' => $idempotencyKey,
            'X-Correlation-Id' => $correlationId,
        ]), $body);
    }
}
