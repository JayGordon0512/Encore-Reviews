<?php

namespace Tests\Feature;

use App\Application\Invitations\IssueReviewInvitationService;
use App\Contracts\ReviewInvitationSender;
use App\Jobs\IssueReviewInvitation;
use App\Mail\ReviewInvitationMail;
use App\Models\IntegrationCredential;
use App\Models\IntegrationProvider;
use App\Models\Organisation;
use App\Models\Performance;
use App\Models\ProtectedReviewerContact;
use App\Models\ReviewConsentEvidence;
use App\Models\ReviewEligibility;
use App\Models\ReviewInvitation;
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
        $this->assertStringContainsString('/review/submit?token=', $message->reviewUrl);
        $this->assertStringContainsString('Share your review', $message->render());

        $token = (string) Str::of($message->reviewUrl)->after('token=');
        $invitation = ReviewInvitation::firstOrFail();
        $this->assertSame('sent', $invitation->status);
        $this->assertNotNull($invitation->sent_at);
        $this->assertNotSame(hash('sha256', $token), $invitation->token_hash);
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

        $this->get('/review/submit?token='.$token)
            ->assertOk()
            ->assertSee('Invitation Test Show');
        $this->postJson('/api/reviews', [
            'invitation_token' => $token,
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

            public function send(string $email, string $displayName, string $showTitle, string $reviewUrl, DateTimeInterface $expiresAt): void
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
        $failedToken = (string) Str::of($failingSender->reviewUrl)->after('token=');
        $this->get('/review/submit?token='.$failedToken)->assertNotFound();
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

            public function send(string $email, string $displayName, string $showTitle, string $reviewUrl, DateTimeInterface $expiresAt): void
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
            public function send(string $email, string $displayName, string $showTitle, string $reviewUrl, DateTimeInterface $expiresAt): void
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
