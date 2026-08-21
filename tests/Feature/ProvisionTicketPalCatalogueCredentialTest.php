<?php

namespace Tests\Feature;

use App\Models\IntegrationCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvisionTicketPalCatalogueCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_provisions_the_staging_catalogue_credential_idempotently_without_storing_the_secret(): void
    {
        config([
            'encore.provider_v2.catalogue_credentials.staging.key_id' => 'ticketpal-catalogue-staging-001',
            'encore.provider_v2.catalogue_credentials.staging.secret_reference' => 'ticketpal-catalogue-staging',
            'encore.provider_v2.secret_references.ticketpal-catalogue-staging' => 'test-secret-never-store',
        ]);

        $this->artisan('encore:provider-v2:provision-ticketpal-catalogue', ['environment' => 'staging'])
            ->assertSuccessful();
        $this->artisan('encore:provider-v2:provision-ticketpal-catalogue', ['environment' => 'staging'])
            ->assertSuccessful();

        $this->assertDatabaseCount('integration_credentials', 1);
        $credential = IntegrationCredential::query()->sole();
        $this->assertSame('ticketpal-catalogue-staging-001', $credential->key_id);
        $this->assertSame('ticketpal-catalogue-staging', $credential->secret_reference);
        $this->assertSame([
            'catalogue-organisation:write',
            'catalogue-membership:write',
            'catalogue-show:write',
            'catalogue-performance:write',
        ], $credential->operation_scopes);
        $this->assertStringNotContainsString('test-secret-never-store', json_encode($credential->toArray()));
    }

    public function test_it_refuses_to_provision_when_the_deployment_secret_is_missing(): void
    {
        config([
            'encore.provider_v2.catalogue_credentials.staging.key_id' => 'ticketpal-catalogue-staging-001',
            'encore.provider_v2.catalogue_credentials.staging.secret_reference' => 'ticketpal-catalogue-staging',
            'encore.provider_v2.secret_references.ticketpal-catalogue-staging' => null,
        ]);

        $this->artisan('encore:provider-v2:provision-ticketpal-catalogue', ['environment' => 'staging'])
            ->assertFailed();

        $this->assertDatabaseCount('integration_credentials', 0);
    }
}
