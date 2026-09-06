<?php

namespace App\Mail;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class ReviewInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $displayName,
        public readonly string $showTitle,
        public readonly string $reviewUrl,
        public readonly DateTimeInterface $expiresAt,
        public readonly string $deliveryId,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Share your review of '.$this->showTitle);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.review-invitation');
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'X-Mailgun-Variables' => json_encode([
                'encore_delivery_id' => $this->deliveryId,
            ], JSON_THROW_ON_ERROR),
        ]);
    }
}
