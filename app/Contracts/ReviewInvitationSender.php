<?php

namespace App\Contracts;

use DateTimeInterface;

interface ReviewInvitationSender
{
    public function send(
        string $email,
        string $displayName,
        string $showTitle,
        string $reviewUrl,
        DateTimeInterface $expiresAt,
        string $deliveryId,
    ): void;
}
