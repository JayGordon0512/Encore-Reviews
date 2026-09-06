<?php

namespace Tests\Feature;

use App\Application\Invitations\IssueReviewInvitationService;
use App\Contracts\ReviewInvitationSender;
use App\Jobs\IssueReviewInvitation;
use App\Mail\ReviewInvitationMail;
use App\Models\IntegrationCredential;
use App\Models\IntegrationProvider;
use App\Models\MailgunDeliveryEvent;
use App\Models\Organisation;
use App\Models\Performance;
use App\Models\ProtectedReviewerContact;
use App\Models\ReviewConsentEvidence;
use App\Models\ReviewEligibility;
use App\Models\ReviewInvitation;
use App\Models\ReviewInvitationDelivery;
use App\Models\ReviewInvitationSchedule;
use App\Models\Show;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class InvitationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://staging.encorereviews.co.uk',
            'encore.provider_v2.invitation_issuing_enabled' => true,
            'encore.invitations.token_digest_key' => 'invitation-digest-test-key',
            'encore.invitations.expiry_days' => 7,
            'encore.invitations.max_attempts' => 3,
            'encore.invitations.retry_delay_minutes' => 15,
            'encore.invitations.claim_timeout_minutes' => 5,
            'encore.mailgun_webhooks.enabled' => false,
            'encore.mailgun_webhooks.signing_key' => 'mailgun-webhook-test-key',
            'encore.mailgun_webhooks.signature_tolerance_seconds' => 300,
        ]);
    }

    public function test_due_invitation_is_dispatched_sent_and_redeemable_without_leaking_sensitive_payloads(): void
    {
        $schedule = $this->createSchedule();
        Mail::fake();

        Queue::fake();
        $this->artisan('encore:invitations:dispatch-due')->assertSuccessful();
        Queue::assertPushed(IssueReviewInvitation::class, fn (IssueReviewInvitation $job): bool => $job->scheduleId === $schedule->id);

        $queuedJob = new IssueReviewInvitation($schedule->id);
        $serializedJob = serialize($queuedJob);
        $this->assertStringNotContainsString('reviewer@example.test', $serializedJob);
        $this->assertStringNotContainsString('Audience Member', $serializedJob);

        $queuedJob->handle($this->app->make(IssueReviewInvitationService::class));

        $message = null;
        Mail::assertSent(ReviewInvitationMail::class, function (ReviewInvitationMail $mail) use (&$message): bool {
            $message = $mail;

            return $mail->hasTo('reviewer@example.test');
        });
        $this->assertInstanceOf(ReviewInvitationMail::class, $message);
        $this->assertSame('Invitation Test Show', $message->showTitle);
        $this->assertStringContainsString('/review/invitation#token=', $message->reviewUrl);
        $this->assertStringNotContainsString('?', $message->reviewUrl);
        $this->assertStringContainsString('Share your review', $message->render());

        $token = rawurldecode((string) Str::of($message->reviewUrl)->after('#token='));
        $invitation = ReviewInvitation::firstOrFail();
        $this->assertSame('sent', $invitation->status);
        $this->assertNotNull($invitation->sent_at);
        $this->assertNotSame(hash('sha256', $token), $invitation->token_hash);
        $delivery = ReviewInvitationDelivery::sole();
        $this->assertSame($delivery->id, $message->deliveryId);
        $this->assertSame(
            json_encode(['encore_delivery_id' => $delivery->id], JSON_THROW_ON_ERROR),
            $message->headers()->text['X-Mailgun-Variables'],
        );
        $this->assertDatabaseHas('review_invitation_schedules', ['id' => $schedule->id, 'status' => 'issued', 'attempts' => 1]);
        $this->assertDatabaseHas('review_invitation_deliveries', ['invitation_id' => $invitation->id, 'status' => 'sent']);

        $persistedOperations = json_encode([
            DB::table('outbox_messages')->get()->all(),
            DB::table('audit_logs')->get()->all(),
            DB::table('review_invitation_deliveries')->get()->all(),
            DB::table('jobs')->get()->all(),
            DB::table('failed_jobs')->get()->all(),
        ], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('reviewer@example.test', $persistedOperations);
        $this->assertStringNotContainsString($token, $persistedOperations);

        $this->postJson('/review/invitation/exchange', ['invitation_token' => $token])
            ->assertOk();
        $this->get('/review/submit')->assertOk()->assertSee('Invitation Test Show');
        $this->postJson(route('review.submit.store'), [
            'email' => 'reviewer@example.test',
            'rating' => 5,
            'would_recommend' => true,
            'content' => 'A memorable performance.',
        ])->assertCreated();

        $this->assertDatabaseCount('reviews', 1);
        $this->assertNotNull($invitation->fresh()->used_at);

        $this->app->make(IssueReviewInvitationService::class)->issue($schedule->id);
        Mail::assertSentCount(1);
        $this->assertDatabaseCount('review_invitations', 1);
    }

    public function test_delivery_failure_revokes_the_token_and_retry_generates_a_replacement(): void
    {
        $schedule = $this->createSchedule();
        $failingSender = new class implements ReviewInvitationSender
        {
            public ?string $reviewUrl = null;

            public function send(string $email, string $displayName, string $showTitle, string $reviewUrl, DateTimeInterface $expiresAt, string $deliveryId): void
            {
                $this->reviewUrl = $reviewUrl;
                throw new RuntimeException('Simulated transport failure containing no retained customer data.');
            }
        };
        $this->app->instance(ReviewInvitationSender::class, $failingSender);

        $this->app->make(IssueReviewInvitationService::class)->issue($schedule->id);

        $failedInvitation = ReviewInvitation::firstOrFail();
        $this->assertSame('revoked', $failedInvitation->status);
        $this->assertSame('delivery_failed', $failedInvitation->revocation_reason);
        $failedToken = rawurldecode((string) Str::of($failingSender->reviewUrl)->after('#token='));
        $this->postJson('/review/invitation/exchange', ['invitation_token' => $failedToken])
            ->assertUnprocessable();
        $this->assertDatabaseHas('review_invitation_schedules', [
            'id' => $schedule->id,
            'status' => 'scheduled',
            'attempts' => 1,
            'last_error_code' => 'mail_transport_failure',
        ]);
        $this->assertDatabaseHas('review_invitation_deliveries', [
            'invitation_id' => $failedInvitation->id,
            'status' => 'failed',
            'error_code' => 'mail_transport_failure',
        ]);

        ReviewInvitationSchedule::whereKey($schedule->id)->update(['scheduled_for' => now()->subMinute()]);
        $sender = new class implements ReviewInvitationSender
        {
            public int $sent = 0;

            public function send(string $email, string $displayName, string $showTitle, string $reviewUrl, DateTimeInterface $expiresAt, string $deliveryId): void
            {
                $this->sent++;
            }
        };
        $this->app->instance(ReviewInvitationSender::class, $sender);
        $this->app->make(IssueReviewInvitationService::class)->issue($schedule->id);

        $this->assertSame(1, $sender->sent);
        $this->assertDatabaseCount('review_invitations', 2);
        $this->assertDatabaseHas('review_invitation_schedules', ['id' => $schedule->id, 'status' => 'issued', 'attempts' => 2]);
        $this->assertSame(1, ReviewInvitation::where('status', 'sent')->count());
        $this->assertSame(1, ReviewInvitation::where('status', 'revoked')->count());
    }

    public function test_disabled_issuing_dispatches_nothing_and_creates_no_invitation(): void
    {
        $this->createSchedule();
        config(['encore.provider_v2.invitation_issuing_enabled' => false]);
        Queue::fake();

        $this->artisan('encore:invitations:dispatch-due')->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('review_invitations', 0);
        $this->assertDatabaseCount('review_invitation_deliveries', 0);
    }

    public function test_exhausted_delivery_attempt_enters_explicit_dead_letter_state(): void
    {
        $schedule = $this->createSchedule();
        config(['encore.invitations.max_attempts' => 1]);
        $this->app->instance(ReviewInvitationSender::class, new class implements ReviewInvitationSender
        {
            public function send(string $email, string $displayName, string $showTitle, string $reviewUrl, DateTimeInterface $expiresAt, string $deliveryId): void
            {
                throw new RuntimeException('Simulated terminal mail outage.');
            }
        });

        $this->app->make(IssueReviewInvitationService::class)->issue($schedule->id);

        $schedule->refresh();
        $this->assertSame('dead_lettered', $schedule->status);
        $this->assertSame('mail_transport_failure', $schedule->last_error_code);
        $this->assertNotNull($schedule->dead_lettered_at);
    }

    public function test_mailgun_webhooks_are_disabled_by_default_and_require_a_valid_signature(): void
    {
        $payload = $this->mailgunPayload((string) Str::uuid(), 'delivered');

        $this->postJson(route('webhooks.mailgun'), $payload)->assertNotFound();

        config(['encore.mailgun_webhooks.enabled' => true]);
        $payload['signature']['signature'] = str_repeat('0', 64);
        $this->postJson(route('webhooks.mailgun'), $payload)->assertUnauthorized();

        $payload = $this->mailgunPayload((string) Str::uuid(), 'delivered', timestamp: now()->subMinutes(10)->timestamp);
        $this->postJson(route('webhooks.mailgun'), $payload)->assertUnauthorized();
        $this->assertDatabaseCount('mailgun_delivery_events', 0);
    }

    public function test_signed_mailgun_delivery_feedback_is_applied_once_without_persisting_the_recipient(): void
    {
        $this->createSchedule();
        Mail::fake();
        $this->app->make(IssueReviewInvitationService::class)->issue(ReviewInvitationSchedule::sole()->id);
        $delivery = ReviewInvitationDelivery::sole();
        config(['encore.mailgun_webhooks.enabled' => true]);
        $payload = $this->mailgunPayload($delivery->id, 'delivered');
        $payload['event-data']['recipient'] = 'reviewer@example.test';

        $this->postJson(route('webhooks.mailgun'), $payload)
            ->assertAccepted()
            ->assertJsonPath('outcome', 'applied');
        $this->postJson(route('webhooks.mailgun'), $payload)
            ->assertAccepted()
            ->assertJsonPath('outcome', 'replayed');

        $delivery->refresh();
        $this->assertSame('delivered', $delivery->status);
        $this->assertNotNull($delivery->delivered_at);
        $this->assertDatabaseCount('mailgun_delivery_events', 1);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'review_invitation.delivery_feedback_received',
            'entity_id' => $delivery->id,
        ]);
        $persisted = json_encode([
            MailgunDeliveryEvent::all()->toArray(),
            DB::table('audit_logs')->where('action', 'review_invitation.delivery_feedback_received')->get()->all(),
        ], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('reviewer@example.test', $persisted);
    }

    public function test_fast_mailgun_delivery_feedback_is_not_overwritten_by_the_sending_worker(): void
    {
        $schedule = $this->createSchedule();
        config(['encore.mailgun_webhooks.enabled' => true]);
        $callback = function (string $deliveryId): void {
            $this->postJson(route('webhooks.mailgun'), $this->mailgunPayload($deliveryId, 'delivered'))
                ->assertAccepted()
                ->assertJsonPath('outcome', 'applied');
        };
        $this->app->instance(ReviewInvitationSender::class, new class($callback) implements ReviewInvitationSender
        {
            public function __construct(private readonly \Closure $callback) {}

            public function send(string $email, string $displayName, string $showTitle, string $reviewUrl, DateTimeInterface $expiresAt, string $deliveryId): void
            {
                ($this->callback)($deliveryId);
            }
        });

        $this->app->make(IssueReviewInvitationService::class)->issue($schedule->id);

        $this->assertSame('delivered', ReviewInvitationDelivery::sole()->status);
        $this->assertSame('sent', ReviewInvitation::sole()->status);
        $this->assertSame('issued', $schedule->fresh()->status);
    }

    public function test_mailgun_complaint_suppresses_the_contact_and_cannot_be_overwritten(): void
    {
        $schedule = $this->createSchedule();
        Mail::fake();
        $this->app->make(IssueReviewInvitationService::class)->issue($schedule->id);
        $delivery = ReviewInvitationDelivery::sole();
        $invitation = $delivery->invitation;
        $contact = $invitation->eligibility->contact;
        config(['encore.mailgun_webhooks.enabled' => true]);
        $complainedAt = now()->subSecond()->timestamp;

        $this->postJson(route('webhooks.mailgun'), $this->mailgunPayload($delivery->id, 'complained', timestamp: $complainedAt))
            ->assertAccepted()
            ->assertJsonPath('outcome', 'applied');
        $this->postJson(route('webhooks.mailgun'), $this->mailgunPayload($delivery->id, 'delivered'))
            ->assertAccepted()
            ->assertJsonPath('outcome', 'ignored_terminal');

        $this->assertSame('complained', $delivery->fresh()->status);
        $this->assertSame('complained', $contact->fresh()->status);
        $this->assertSame('revoked', $invitation->fresh()->status);
        $this->assertSame('mailgun_complaint', $invitation->fresh()->revocation_reason);
        $this->assertDatabaseCount('mailgun_delivery_events', 2);
    }

    public function test_mailgun_permanent_and_temporary_failures_have_distinct_outcomes(): void
    {
        $schedule = $this->createSchedule();
        Mail::fake();
        $this->app->make(IssueReviewInvitationService::class)->issue($schedule->id);
        $delivery = ReviewInvitationDelivery::sole();
        config(['encore.mailgun_webhooks.enabled' => true]);

        $this->postJson(route('webhooks.mailgun'), $this->mailgunPayload($delivery->id, 'failed', 'temporary'))
            ->assertAccepted()
            ->assertJsonPath('outcome', 'applied');
        $this->assertSame('temporarily_failed', $delivery->fresh()->status);
        $this->assertSame('active', $delivery->invitation->eligibility->contact->fresh()->status);

        $this->postJson(route('webhooks.mailgun'), $this->mailgunPayload($delivery->id, 'failed', 'permanent'))
            ->assertAccepted()
            ->assertJsonPath('outcome', 'applied');
        $this->assertSame('failed', $delivery->fresh()->status);
        $this->assertSame('undeliverable', $delivery->invitation->eligibility->contact->fresh()->status);
        $this->assertSame('mailgun_permanent_failure', $delivery->invitation->fresh()->revocation_reason);
    }

    /** @return array<string, mixed> */
    private function mailgunPayload(
        string $deliveryId,
        string $eventType,
        ?string $severity = null,
        ?int $timestamp = null,
    ): array {
        $timestamp ??= now()->timestamp;
        $token = 'mailgun-token-'.Str::random(24);

        return [
            'signature' => [
                'timestamp' => (string) $timestamp,
                'token' => $token,
                'signature' => hash_hmac('sha256', $timestamp.$token, 'mailgun-webhook-test-key'),
            ],
            'event-data' => array_filter([
                'id' => (string) Str::uuid(),
                'event' => $eventType,
                'timestamp' => $timestamp,
                'severity' => $severity,
                'delivery-status' => ['code' => $severity === 'permanent' ? 550 : 421],
                'user-variables' => ['encore_delivery_id' => $deliveryId],
            ], fn (mixed $value): bool => $value !== null),
        ];
    }

    private function createSchedule(): ReviewInvitationSchedule
    {
        $provider = IntegrationProvider::create(['slug' => 'ticketpal', 'name' => 'TicketPal', 'is_active' => true]);
        $credential = IntegrationCredential::create([
            'provider_id' => $provider->id,
            'key_id' => 'invitation-test-key',
            'account_reference' => 'ticketpal-staging',
            'secret_reference' => 'fixture://invitation-test',
            'operation_scopes' => ['review-eligibility:write'],
            'activated_at' => now()->subDay(),
        ]);
        $organisation = Organisation::create(['name' => 'Invitation Theatre', 'is_active' => true]);
        $show = Show::create([
            'organisation_id' => $organisation->id,
            'title' => 'Invitation Test Show',
            'slug' => 'invitation-test-show',
            'ticket_url' => 'https://tickets.example.test/invitation-show',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'invitation-show-1',
            'status' => 'now_playing',
        ]);
        $performance = Performance::create([
            'show_id' => $show->id,
            'starts_at' => now()->subHours(3),
            'ends_at' => now()->subHour(),
            'status' => 'completed',
            'provider_source' => 'ticketpal',
            'provider_event_id' => 'invitation-show-1',
            'provider_performance_id' => 'invitation-performance-1',
        ]);
        $contact = ProtectedReviewerContact::create([
            'email_ciphertext' => Crypt::encryptString('reviewer@example.test'),
            'display_name_ciphertext' => Crypt::encryptString('Audience Member'),
            'email_fingerprint' => hash_hmac('sha256', 'reviewer@example.test', 'test-contact-key'),
            'fingerprint_version' => 1,
            'status' => 'active',
        ]);
        $consent = ReviewConsentEvidence::create([
            'provider_id' => $provider->id,
            'credential_id' => $credential->id,
            'organisation_id' => $organisation->id,
            'account_reference' => 'ticketpal-staging',
            'provider_event_id' => (string) Str::uuid(),
            'provider_booking_id' => 'booking-1',
            'purpose' => 'encore_review',
            'policy_version' => '2026-08',
            'captured_at' => now()->subDay(),
            'evidence_digest' => hash('sha256', 'test-evidence'),
            'created_at' => now(),
        ]);
        $eligibility = ReviewEligibility::create([
            'provider_id' => $provider->id,
            'credential_id' => $credential->id,
            'organisation_id' => $organisation->id,
            'account_reference' => 'ticketpal-staging',
            'show_id' => $show->id,
            'performance_id' => $performance->id,
            'contact_id' => $contact->id,
            'consent_evidence_id' => $consent->id,
            'provider_event_id' => $consent->provider_event_id,
            'provider_booking_id' => 'booking-1',
            'purpose' => 'encore_review',
            'admission_quantity' => 2,
            'status' => 'eligible',
            'occurred_at' => now()->subDay(),
        ]);

        return ReviewInvitationSchedule::create([
            'eligibility_id' => $eligibility->id,
            'correlation_id' => (string) Str::uuid(),
            'scheduled_for' => now()->subMinute(),
            'status' => 'scheduled',
        ]);
    }
}
