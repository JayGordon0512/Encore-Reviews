<?php

namespace Tests\Feature;

use App\Domain\ReviewEligibility\EligibilityIdGenerator;
use App\Models\IntegrationCredential;
use App\Models\IntegrationOrganisationMapping;
use App\Models\IntegrationPerformanceMapping;
use App\Models\IntegrationProvider;
use App\Models\IntegrationShowMapping;
use App\Models\Organisation;
use App\Models\Performance;
use App\Models\Show;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProviderV2ContractHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_all_shared_fixtures_run_through_the_real_http_boundary(): void
    {
        $root = dirname(__DIR__).'/Fixtures/ProviderApiV2';
        $manifest = json_decode(file_get_contents($root.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $credentialDocument = json_decode(file_get_contents($root.'/test-credentials.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->seedContractContext($credentialDocument['credentials']);
        config([
            'encore.provider_v2.ingress_enabled' => true,
            'encore.provider_v2.invitation_issuing_enabled' => false,
            'encore.provider_v2.contact_fingerprint_key' => 'fixture-contact-fingerprint-key-do-not-use',
            'encore.provider_v2.secret_references' => collect($credentialDocument['credentials'])
                ->mapWithKeys(fn (array $credential): array => [
                    'fixture://'.$credential['key_id'] => $credential['secret'],
                ])->all(),
        ]);
        $this->app->instance(EligibilityIdGenerator::class, new class implements EligibilityIdGenerator
        {
            public function generate(string $providerEventId): string
            {
                return '55555555-5555-4555-8555-555555555555';
            }
        });

        foreach ($manifest['cases'] as $case) {
            CarbonImmutable::setTestNow($case['validation_clock']);
            $body = file_get_contents($root.'/'.$case['body_file']);
            $expected = json_decode(file_get_contents($root.'/'.$case['expected_response_file']), true, flags: JSON_THROW_ON_ERROR);
            $response = $this->sendRawFixture($case['method'], $case['path'], $case['headers'], $body);

            $this->assertSame(
                $case['expected_http_status'],
                $response->getStatusCode(),
                "Fixture {$case['id']} returned ".json_encode($response->json()),
            );
            $this->assertSame($expected, $response->json(), "Fixture {$case['id']} response changed");
            $response->assertHeader('X-Correlation-Id', $case['headers']['X-Correlation-Id']);

            if ($case['id'] === 'eligibility.accepted') {
                $this->assertDatabaseHas('review_invitation_schedules', [
                    'eligibility_id' => '55555555-5555-4555-8555-555555555555',
                    'status' => 'suppressed',
                    'suppression_reason' => 'invitation_issuing_disabled',
                ]);
            }
        }

        $this->assertDatabaseCount('review_eligibilities', 1);
        $this->assertDatabaseHas('review_eligibilities', ['status' => 'withdrawn']);
        $this->assertDatabaseCount('review_consent_evidence', 1);
        $this->assertDatabaseCount('protected_reviewer_contacts', 1);
        $this->assertDatabaseHas('review_invitation_schedules', [
            'eligibility_id' => '55555555-5555-4555-8555-555555555555',
            'status' => 'cancelled',
            'suppression_reason' => 'consent_withdrawn',
        ]);
        $this->assertDatabaseCount('review_eligibility_withdrawals', 2);
        $this->assertDatabaseCount('outbox_messages', 3);

        $storedEvidence = json_encode([
            DB::table('outbox_messages')->pluck('payload')->all(),
            DB::table('integration_request_journals')->get()->all(),
        ], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('alex.morgan@example.test', $storedEvidence);
        $this->assertStringNotContainsString('encore-provider-v2-fixture-secret', $storedEvidence);
        $this->assertStringNotContainsString('X-Request-Signature', $storedEvidence);
    }

    public function test_failed_domain_transaction_keeps_only_security_evidence(): void
    {
        $root = dirname(__DIR__).'/Fixtures/ProviderApiV2';
        $manifest = json_decode(file_get_contents($root.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $credentialDocument = json_decode(file_get_contents($root.'/test-credentials.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->seedContractContext($credentialDocument['credentials']);
        config([
            'encore.provider_v2.ingress_enabled' => true,
            'encore.provider_v2.contact_fingerprint_key' => null,
            'encore.provider_v2.secret_references' => collect($credentialDocument['credentials'])
                ->mapWithKeys(fn (array $credential): array => [
                    'fixture://'.$credential['key_id'] => $credential['secret'],
                ])->all(),
        ]);
        $case = $manifest['cases'][0];
        CarbonImmutable::setTestNow($case['validation_clock']);
        $body = file_get_contents($root.'/'.$case['body_file']);

        $this->sendRawFixture($case['method'], $case['path'], $case['headers'], $body)
            ->assertStatus(503)
            ->assertJsonPath('error', 'temporarily_unavailable');

        $this->assertDatabaseCount('review_eligibilities', 0);
        $this->assertDatabaseCount('review_consent_evidence', 0);
        $this->assertDatabaseCount('protected_reviewer_contacts', 0);
        $this->assertDatabaseCount('review_invitation_schedules', 0);
        $this->assertDatabaseCount('integration_idempotency_records', 0);
        $this->assertDatabaseCount('outbox_messages', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertDatabaseCount('integration_request_nonces', 1);
        $this->assertDatabaseCount('integration_request_journals', 1);
    }

    /** @param array<string, string> $headers */
    private function sendRawFixture(string $method, string $path, array $headers, string $body): TestResponse
    {
        return $this->call(
            $method, $path, [], [], [], $this->transformHeadersToServerVars($headers), $body,
        );
    }

    /** @param list<array<string, mixed>> $credentialFixtures */
    private function seedContractContext(array $credentialFixtures): void
    {
        CarbonImmutable::setTestNow('2026-08-04T11:00:00Z');
        $provider = IntegrationProvider::create(['slug' => 'ticketpal', 'name' => 'TicketPal', 'is_active' => true]);
        $organisation = Organisation::create(['name' => 'Fixture Theatre', 'is_active' => true]);
        foreach ($credentialFixtures as $fixture) {
            $credential = IntegrationCredential::create([
                'provider_id' => $provider->id,
                'key_id' => $fixture['key_id'],
                'account_reference' => 'ticketpal-main',
                'secret_reference' => 'fixture://'.$fixture['key_id'],
                'operation_scopes' => $fixture['scopes'],
                'activated_at' => '2026-08-01T00:00:00Z',
                'expires_at' => $fixture['expired_at'] ?? null,
                'revoked_at' => $fixture['state'] === 'revoked' ? '2026-08-04T11:00:00Z' : null,
            ]);
            $credential->organisations()->attach($organisation->id);
        }

        $show = Show::create([
            'organisation_id' => $organisation->id, 'title' => 'Fixture Show', 'slug' => 'fixture-show',
            'ticket_url' => 'https://tickets.example.test/show', 'provider_source' => 'ticketpal',
            'provider_event_id' => 'TP-SHOW-501', 'status' => 'upcoming',
        ]);
        $performance = Performance::create([
            'show_id' => $show->id, 'starts_at' => '2026-08-04T18:00:00Z',
            'ends_at' => '2026-08-04T20:00:00Z', 'status' => 'scheduled',
            'provider_source' => 'ticketpal', 'provider_event_id' => 'TP-SHOW-501',
            'provider_performance_id' => 'TP-PERF-9001',
        ]);
        $organisationMapping = IntegrationOrganisationMapping::create([
            'provider_id' => $provider->id, 'account_reference' => 'ticketpal-main',
            'external_organisation_id' => 'TP-ORG-1', 'organisation_id' => $organisation->id,
        ]);
        $showMapping = IntegrationShowMapping::create([
            'organisation_mapping_id' => $organisationMapping->id, 'provider_id' => $provider->id,
            'account_reference' => 'ticketpal-main', 'external_show_id' => 'TP-SHOW-501',
            'organisation_id' => $organisation->id, 'show_id' => $show->id,
        ]);
        IntegrationPerformanceMapping::create([
            'show_mapping_id' => $showMapping->id, 'provider_id' => $provider->id,
            'account_reference' => 'ticketpal-main', 'external_performance_id' => 'TP-PERF-9001',
            'organisation_id' => $organisation->id, 'show_id' => $show->id,
            'performance_id' => $performance->id,
        ]);
    }
}
