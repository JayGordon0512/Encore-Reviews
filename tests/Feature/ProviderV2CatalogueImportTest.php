<?php

namespace Tests\Feature;

use App\Models\IntegrationCredential;
use App\Models\IntegrationProvider;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProviderV2CatalogueImportTest extends TestCase
{
    use RefreshDatabase;

    private const KEY_ID = 'ticketpal-catalogue-test-key';

    private const SECRET = 'ticketpal-catalogue-test-secret';

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-08-21T10:00:00Z');
        $provider = IntegrationProvider::create([
            'slug' => 'ticketpal',
            'name' => 'TicketPal',
            'is_active' => true,
        ]);
        IntegrationCredential::create([
            'provider_id' => $provider->id,
            'key_id' => self::KEY_ID,
            'account_reference' => 'ticketpal-main',
            'secret_reference' => 'fixture://ticketpal-catalogue',
            'operation_scopes' => [
                'catalogue-organisation:write',
                'catalogue-membership:write',
                'catalogue-show:write',
                'catalogue-performance:write',
            ],
            'activated_at' => now()->subMinute(),
        ]);
        config([
            'encore.provider_v2.ingress_enabled' => true,
            'encore.provider_v2.secret_references' => [
                'fixture://ticketpal-catalogue' => self::SECRET,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_catalogue_import_upserts_tenant_safe_mappings_and_preserves_multi_organisation_admins(): void
    {
        foreach ([
            ['id' => 'TP-ORG-1', 'name' => 'First Theatre', 'key' => 'org-1'],
            ['id' => 'TP-ORG-2', 'name' => 'Second Theatre', 'key' => 'org-2'],
        ] as $organisation) {
            $this->signedPost('/api/v2/integrations/catalogue/organisations', [
                'schema_version' => '2.0',
                'provider' => 'ticketpal',
                'provider_organisation_id' => $organisation['id'],
                'name' => $organisation['name'],
                'status' => 'active',
            ], $organisation['key'])->assertAccepted()->assertJsonPath('action', 'created');
        }

        foreach (['TP-ORG-1', 'TP-ORG-2'] as $index => $organisationId) {
            $this->signedPost('/api/v2/integrations/catalogue/organisation-user-memberships', [
                'schema_version' => '2.0',
                'provider' => 'ticketpal',
                'provider_organisation_id' => $organisationId,
                'provider_user_id' => 'TP-USER-9',
                'name' => 'TicketPal Owner',
                'email' => 'owner@example.test',
                'role' => 'owner',
                'status' => 'active',
            ], 'membership-'.($index + 1))->assertAccepted();
        }

        $showPayload = [
            'schema_version' => '2.0',
            'provider' => 'ticketpal',
            'provider_organisation_id' => 'TP-ORG-1',
            'provider_show_id' => 'TP-SHOW-100',
            'title' => 'Historical Show',
            'description' => 'Imported catalogue description.',
            'category' => 'theatre',
            'status' => 'archived',
            'image_url' => 'https://ticketpal.example.test/images/show-100.jpg',
            'public_url' => 'https://ticketpal.example.test/shows/100',
        ];
        $showResponse = $this->signedPost('/api/v2/integrations/catalogue/shows', $showPayload, 'show-100')
            ->assertAccepted()
            ->assertJsonPath('action', 'created');
        $showId = $showResponse->json('mapping.show_id');

        $this->signedPost('/api/v2/integrations/catalogue/performances', [
            'schema_version' => '2.0',
            'provider' => 'ticketpal',
            'provider_show_id' => 'TP-SHOW-100',
            'provider_performance_id' => 'TP-PERF-100-A',
            'starts_at' => '2024-06-01T19:30:00Z',
            'ends_at' => '2024-06-01T21:30:00Z',
            'status' => 'completed',
            'location' => [
                'type' => 'venue',
                'name' => 'Encore Theatre',
                'city' => 'London',
                'postcode' => 'W1D 6QF',
                'country' => 'GB',
            ],
        ], 'performance-100-a')->assertAccepted()->assertJsonPath('action', 'created');

        $this->assertDatabaseCount('organisations', 2);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('organisation_user_memberships', 2);
        $this->assertDatabaseCount('integration_user_mappings', 1);
        $this->assertDatabaseCount('shows', 1);
        $this->assertDatabaseCount('performances', 1);
        $this->assertDatabaseHas('shows', [
            'id' => $showId,
            'primary_image_path' => 'https://ticketpal.example.test/images/show-100.jpg',
            'status' => 'archived',
            'lifecycle_status' => 'archived',
            'reviews_locked' => true,
        ]);

        $this->signedPost('/api/v2/integrations/catalogue/shows', $showPayload, 'show-100')
            ->assertAccepted()
            ->assertJsonPath('status', 'duplicate')
            ->assertJsonPath('action', 'unchanged')
            ->assertJsonPath('mapping.show_id', $showId);

        $changedPayload = $showPayload;
        $changedPayload['title'] = 'Changed with reused idempotency key';
        $this->signedPost('/api/v2/integrations/catalogue/shows', $changedPayload, 'show-100')
            ->assertConflict()
            ->assertJsonPath('error', 'idempotency_conflict');
    }

    public function test_child_import_requires_an_existing_parent_mapping(): void
    {
        $this->signedPost('/api/v2/integrations/catalogue/shows', [
            'schema_version' => '2.0',
            'provider' => 'ticketpal',
            'provider_organisation_id' => 'TP-MISSING',
            'provider_show_id' => 'TP-SHOW-404',
            'title' => 'Unmapped Show',
            'status' => 'upcoming',
            'public_url' => 'https://ticketpal.example.test/shows/404',
        ], 'missing-parent')->assertUnprocessable()->assertJsonPath('details.0.code', 'mapping_not_found');

        $this->assertDatabaseCount('shows', 0);
        $this->assertDatabaseCount('integration_idempotency_records', 0);
    }

    /** @param array<string, mixed> $payload */
    private function signedPost(string $path, array $payload, string $idempotencyKey): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $timestamp = now()->toRfc3339String();
        $nonce = (string) Str::uuid();
        $correlationId = (string) Str::uuid();
        $digest = hash('sha256', $body);
        $canonical = implode("\n", ['POST', $path, $timestamp, $nonce, $digest]);
        $headers = [
            'Content-Type' => 'application/json',
            'X-Provider-Key-Id' => self::KEY_ID,
            'X-Request-Timestamp' => $timestamp,
            'X-Request-Nonce' => $nonce,
            'X-Request-Signature' => 'v1='.hash_hmac('sha256', $canonical, self::SECRET),
            'Idempotency-Key' => $idempotencyKey,
            'X-Correlation-Id' => $correlationId,
        ];

        return $this->call('POST', $path, [], [], [], $this->transformHeadersToServerVars($headers), $body);
    }
}
