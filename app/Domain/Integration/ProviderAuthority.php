<?php

namespace App\Domain\Integration;

use App\Models\IntegrationCredential;

final readonly class ProviderAuthority
{
    /** @param list<string> $operationScopes */
    public function __construct(
        public IntegrationCredential $credential,
        public string $providerSlug,
        public string $accountReference,
        public array $operationScopes,
    ) {}

    public function allows(string $operation): bool
    {
        return in_array($operation, $this->operationScopes, true);
    }
}
