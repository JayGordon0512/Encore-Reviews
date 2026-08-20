<?php

namespace Tests\Feature;

use App\Models\IntegrationCredential;
use App\Models\IntegrationIdempotencyRecord;
use App\Models\IntegrationOrganisationMapping;
use App\Models\IntegrationPerformanceMapping;
use App\Models\IntegrationProvider;
use App\Models\IntegrationRequestNonce;
use App\Models\IntegrationShowMapping;
use App\Models\Organisation;
use App\Models\Performance;
use App\Models\Show;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProviderV2PersistenceFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_v2_capabilities_are_disabled_and_routes_are_absent(): void
    {
        $this->assertFalse(config('encore.provider_v2.ingress_enabled'));
        $this->assertFalse(config('encore.provider_v2.invitation_issuing_enabled'));

        $this->postJson('/api/v2/integrations/review-invitation-eligibilities')
            ->assertNotFound();
        $this->postJson('/api/v2/integrations/review-invitation-withdrawals')
            ->assertNotFound();
    }

    public function test_credentials_hold_secret_references_and_explicit_tenant_scope(): void
    {
        $provider = $this->createProvider();
        $organisation = $this->createOrganisation('Tenant A');
        $credential = $this->createCredential($provider);

        $credential->organisations()->attach($organisation->id);
        $credential->refresh();

        $this->assertSame(
            ['review-eligibility:write', 'review-withdrawal:write'],
            $credential->operation_scopes,
        );
        $this->assertSame('secret-manager://encore/ticketpal/test-key', $credential->secret_reference);
        $this->assertArrayNotHasKey('secret_reference', $credential->toArray());
        $this->assertTrue($credential->organisations->contains($organisation));
        $this->assertFalse(Schema::hasColumn('integration_credentials', 'secret'));
    }

    public function test_show_mapping_cannot_cross_organisation_boundary(): void
    {
        $provider = $this->createProvider();
        $tenantA = $this->createOrganisation('Tenant A');
        $tenantB = $this->createOrganisation('Tenant B');
        $tenantBShow = $this->createShow($tenantB, 'tenant-b-show');
        $organisationMapping = $this->createOrganisationMapping($provider, $tenantA);

        $this->expectException(QueryException::class);

        IntegrationShowMapping::create([
            'organisation_mapping_id' => $organisationMapping->id,
            'provider_id' => $provider->id,
            'account_reference' => 'ticketpal-main',
            'external_show_id' => 'tp-show-cross-tenant',
            'organisation_id' => $tenantA->id,
            'show_id' => $tenantBShow->id,
        ]);
    }

    public function test_performance_mapping_must_match_its_tenant_safe_show_parent(): void
    {
        $provider = $this->createProvider();
        $tenant = $this->createOrganisation('Tenant A');
        $mappedShow = $this->createShow($tenant, 'mapped-show');
        $otherShow = $this->createShow($tenant, 'other-show');
        $otherPerformance = $this->createPerformance($otherShow, 'other-performance');
        $organisationMapping = $this->createOrganisationMapping($provider, $tenant);
        $showMapping = IntegrationShowMapping::create([
            'organisation_mapping_id' => $organisationMapping->id,
            'provider_id' => $provider->id,
            'account_reference' => 'ticketpal-main',
            'external_show_id' => 'tp-show-mapped',
            'organisation_id' => $tenant->id,
            'show_id' => $mappedShow->id,
        ]);

        $this->expectException(QueryException::class);

        IntegrationPerformanceMapping::create([
            'show_mapping_id' => $showMapping->id,
            'provider_id' => $provider->id,
            'account_reference' => 'ticketpal-main',
            'external_performance_id' => 'tp-performance-wrong-parent',
            'organisation_id' => $tenant->id,
            'show_id' => $mappedShow->id,
            'performance_id' => $otherPerformance->id,
        ]);
    }

    public function test_nonce_is_single_use_per_credential(): void
    {
        $credential = $this->createCredential($this->createProvider());
        $nonce = (string) Str::uuid();
        $attributes = [
            'credential_id' => $credential->id,
            'nonce' => $nonce,
            'request_timestamp' => now(),
            'received_at' => now(),
            'expires_at' => now()->addMinutes(10),
            'correlation_id' => (string) Str::uuid(),
        ];

        IntegrationRequestNonce::create($attributes);

        $this->expectException(QueryException::class);
        IntegrationRequestNonce::create([
            ...$attributes,
            'correlation_id' => (string) Str::uuid(),
        ]);
    }

    public function test_idempotency_key_is_scoped_by_credential_and_operation(): void
    {
        $provider = $this->createProvider();
        $firstCredential = $this->createCredential($provider);
        $secondCredential = $this->createCredential(
            $provider,
            'ticketpal-v2-test-key-0002',
            'secret-manager://encore/ticketpal/test-key-2',
        );
        $attributes = [
            'operation' => 'review-eligibility:write',
            'idempotency_key' => 'idem-v2-eligibility-000001',
            'request_digest' => str_repeat('a', 64),
            'status' => 'processing',
            'first_correlation_id' => (string) Str::uuid(),
            'last_correlation_id' => (string) Str::uuid(),
        ];

        IntegrationIdempotencyRecord::create([
            ...$attributes,
            'credential_id' => $firstCredential->id,
        ]);
        IntegrationIdempotencyRecord::create([
            ...$attributes,
            'credential_id' => $secondCredential->id,
        ]);

        $this->assertDatabaseCount('integration_idempotency_records', 2);

        $this->expectException(QueryException::class);
        IntegrationIdempotencyRecord::create([
            ...$attributes,
            'credential_id' => $firstCredential->id,
        ]);
    }

    public function test_request_journal_has_no_raw_body_signature_or_secret_columns(): void
    {
        $columns = Schema::getColumnListing('integration_request_journals');

        $this->assertNotContains('raw_body', $columns);
        $this->assertNotContains('request_body', $columns);
        $this->assertNotContains('signature', $columns);
        $this->assertNotContains('secret', $columns);
        $this->assertContains('body_digest', $columns);
        $this->assertContains('credential_key_fingerprint', $columns);
    }

    private function createProvider(): IntegrationProvider
    {
        return IntegrationProvider::create([
            'slug' => 'ticketpal',
            'name' => 'TicketPal',
            'is_active' => true,
        ]);
    }

    private function createCredential(
        IntegrationProvider $provider,
        string $keyId = 'ticketpal-v2-test-key-0001',
        string $secretReference = 'secret-manager://encore/ticketpal/test-key',
    ): IntegrationCredential {
        return IntegrationCredential::create([
            'provider_id' => $provider->id,
            'key_id' => $keyId,
            'account_reference' => 'ticketpal-main',
            'secret_reference' => $secretReference,
            'operation_scopes' => [
                'review-eligibility:write',
                'review-withdrawal:write',
            ],
            'activated_at' => now()->subMinute(),
        ]);
    }

    private function createOrganisation(string $name): Organisation
    {
        return Organisation::create([
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function createOrganisationMapping(
        IntegrationProvider $provider,
        Organisation $organisation,
    ): IntegrationOrganisationMapping {
        return IntegrationOrganisationMapping::create([
            'provider_id' => $provider->id,
            'account_reference' => 'ticketpal-main',
            'external_organisation_id' => 'tp-'.Str::slug($organisation->name),
            'organisation_id' => $organisation->id,
        ]);
    }

    private function createShow(Organisation $organisation, string $identity): Show
    {
        return Show::create([
            'organisation_id' => $organisation->id,
            'title' => Str::headline($identity),
            'slug' => $identity,
            'ticket_url' => "https://tickets.example.com/{$identity}",
            'provider_source' => 'ticketpal',
            'provider_event_id' => $identity,
            'status' => 'upcoming',
        ]);
    }

    private function createPerformance(Show $show, string $identity): Performance
    {
        return Performance::create([
            'show_id' => $show->id,
            'starts_at' => now()->addDay(),
            'status' => 'scheduled',
            'provider_source' => 'ticketpal',
            'provider_event_id' => $show->provider_event_id,
            'provider_performance_id' => $identity,
        ]);
    }
}
