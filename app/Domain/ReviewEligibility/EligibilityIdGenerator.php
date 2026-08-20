<?php

namespace App\Domain\ReviewEligibility;

interface EligibilityIdGenerator
{
    public function generate(string $providerEventId): string;
}
