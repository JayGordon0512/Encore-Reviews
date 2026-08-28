<?php

namespace App\Infrastructure\Notifications;

use App\Contracts\ReviewInvitationSender;
use App\Mail\ReviewInvitationMail;
use DateTimeInterface;
use Illuminate\Support\Facades\Mail;

final class LaravelReviewInvitationSender implements ReviewInvitationSender
{
    public function send(
        string $email,
        string $displayName,
        string $showTitle,
        string $reviewUrl,
        DateTimeInterface $expiresAt,
    ): void {
        Mail::to($email)->send(new ReviewInvitationMail(
            $displayName,
            $showTitle,
            $reviewUrl,
            $expiresAt,
        ));
    }
}
