<?php

namespace App\Console\Commands;

use App\Contracts\ProviderSecretResolver;
use App\Models\IntegrationCredential;
use App\Models\IntegrationProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProvisionTicketPalCatalogueCredential extends Command
{
    private const OPERATION_SCOPES = [
        'catalogue-organisation:write',
        'catalogue-membership:write',
        'catalogue-show:write',
        'catalogue-performance:write',
    ];

    protected $signature = 'encore:provider-v2:provision-ticketpal-catalogue
        {environment=staging : Credential environment: staging or production}';

    protected $description = 'Provision a secret-referenced TicketPal Provider v2 catalogue credential';

    public function handle(ProviderSecretResolver $secrets): int
    {
        $environment = (string) $this->argument('environment');
        if (! in_array($environment, ['staging', 'production'], true)) {
            $this->error('Environment must be staging or production.');

            return self::FAILURE;
        }

        $configuration = config("encore.provider_v2.catalogue_credentials.{$environment}");
        $keyId = is_array($configuration) ? trim((string) ($configuration['key_id'] ?? '')) : '';
        $secretReference = is_array($configuration) ? trim((string) ($configuration['secret_reference'] ?? '')) : '';
        if ($keyId === '' || $secretReference === '' || $secrets->resolve($secretReference) === null) {
            $this->error("TicketPal {$environment} catalogue credentials are not fully configured.");

            return self::FAILURE;
        }

        try {
            $credential = DB::transaction(function () use ($keyId, $secretReference): IntegrationCredential {
                $provider = IntegrationProvider::query()->firstOrCreate(
                    ['slug' => 'ticketpal'],
                    ['name' => 'TicketPal', 'is_active' => true],
                );
                if (! $provider->is_active) {
                    throw new RuntimeException('The TicketPal integration provider is inactive.');
                }

                $credential = IntegrationCredential::query()->where('key_id', $keyId)->first();
                if ($credential && $credential->provider_id !== $provider->id) {
                    throw new RuntimeException('The configured key ID belongs to another provider.');
                }
                if ($credential?->revoked_at !== null) {
                    throw new RuntimeException('A revoked credential cannot be reprovisioned. Rotate to a new key ID.');
                }

                $credential ??= new IntegrationCredential([
                    'provider_id' => $provider->id,
                    'key_id' => $keyId,
                    'activated_at' => now(),
                ]);
                $credential->fill([
                    'account_reference' => 'ticketpal-main',
                    'secret_reference' => $secretReference,
                    'operation_scopes' => self::OPERATION_SCOPES,
                ])->save();

                return $credential;
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("TicketPal {$environment} catalogue credential {$credential->key_id} is provisioned.");

        return self::SUCCESS;
    }
}
