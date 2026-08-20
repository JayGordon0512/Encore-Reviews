<?php

namespace App\Infrastructure\Persistence;

use App\Domain\ReviewEligibility\EligibilityIdGenerator;
use Illuminate\Support\Str;

final class UuidEligibilityIdGenerator implements EligibilityIdGenerator
{
    public function generate(string $providerEventId): string
    {
        return (string) Str::uuid();
    }
}
