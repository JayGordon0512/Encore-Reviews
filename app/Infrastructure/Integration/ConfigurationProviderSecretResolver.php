<?php

namespace App\Infrastructure\Integration;

use App\Contracts\ProviderSecretResolver;

final class ConfigurationProviderSecretResolver implements ProviderSecretResolver
{
    public function resolve(string $reference): ?string
    {
        $secret = config("encore.provider_v2.secret_references.{$reference}");

        return is_string($secret) && $secret !== '' ? $secret : null;
    }
}
