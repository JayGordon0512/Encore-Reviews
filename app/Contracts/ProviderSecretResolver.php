<?php

namespace App\Contracts;

interface ProviderSecretResolver
{
    public function resolve(string $reference): ?string;
}
