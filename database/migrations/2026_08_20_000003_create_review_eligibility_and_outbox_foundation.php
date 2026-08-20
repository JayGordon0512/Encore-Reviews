<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protected_reviewer_contacts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->text('email_ciphertext');
            $table->text('display_name_ciphertext');
            $table->char('email_fingerprint', 64);
            $table->unsignedSmallInteger('fingerprint_version');
            $table->string('status', 32)->default('active');
            $table->timestampsTz();

            $table->unique(
                ['fingerprint_version', 'email_fingerprint'],
                'protected_contacts_fingerprint_unique',
            );
        });

        Schema::create('review_consent_evidence', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('integration_providers')->restrictOnDelete();
            $table->foreignUuid('credential_id')->constrained('integration_credentials')->restrictOnDelete();
            $table->foreignUuid('organisation_id')->constrained()->restrictOnDelete();
            $table->string('account_reference');
            $table->uuid('provider_event_id');
            $table->string('provider_booking_id', 100);
            $table->string('purpose', 64);
            $table->string('policy_version', 100);
            $table->timestampTz('captured_at');
            $table->char('evidence_digest', 64);
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(
                ['provider_id', 'account_reference', 'provider_event_id'],
                'review_consent_provider_event_unique',
            );
        });

        Schema::create('review_eligibilities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('integration_providers')->restrictOnDelete();
            $table->foreignUuid('credential_id')->constrained('integration_credentials')->restrictOnDelete();
            $table->uuid('organisation_id');
            $table->string('account_reference');
            $table->uuid('show_id');
            $table->uuid('performance_id');
            $table->foreignUuid('contact_id')->constrained('protected_reviewer_contacts')->restrictOnDelete();
            $table->foreignUuid('consent_evidence_id')->constrained('review_consent_evidence')->restrictOnDelete();
            $table->uuid('provider_event_id');
            $table->string('provider_booking_id', 100);
            $table->string('purpose', 64);
            $table->unsignedSmallInteger('admission_quantity');
            $table->string('status', 32)->default('eligible');
            $table->timestampTz('occurred_at');
            $table->timestampTz('withdrawn_at')->nullable();
            $table->timestampsTz();

            $table->foreign(
                ['show_id', 'organisation_id'],
                'review_eligibilities_show_tenant_fk',
            )->references(['id', 'organisation_id'])->on('shows')->restrictOnDelete();
            $table->foreign(
                ['performance_id', 'show_id'],
                'review_eligibilities_performance_show_fk',
            )->references(['id', 'show_id'])->on('performances')->restrictOnDelete();
            $table->unique(
                ['provider_id', 'account_reference', 'provider_booking_id', 'purpose'],
                'review_eligibilities_booking_purpose_unique',
            );
            $table->unique(
                ['provider_id', 'account_reference', 'provider_event_id'],
                'review_eligibilities_provider_event_unique',
            );
            $table->index(['organisation_id', 'status']);
        });

        Schema::create('review_invitation_schedules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('eligibility_id')->unique()->constrained('review_eligibilities')->restrictOnDelete();
            $table->timestampTz('scheduled_for');
            $table->string('status', 32)->default('scheduled');
            $table->string('suppression_reason', 64)->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'scheduled_for']);
        });

        Schema::create('review_eligibility_withdrawals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('integration_providers')->restrictOnDelete();
            $table->foreignUuid('credential_id')->constrained('integration_credentials')->restrictOnDelete();
            $table->foreignUuid('eligibility_id')->nullable()->constrained('review_eligibilities')->restrictOnDelete();
            $table->string('account_reference');
            $table->uuid('provider_event_id');
            $table->uuid('original_eligibility_event_id')->nullable();
            $table->string('provider_booking_id', 100);
            $table->string('purpose', 64);
            $table->timestampTz('withdrawn_at');
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(
                ['provider_id', 'account_reference', 'provider_event_id'],
                'review_withdrawals_provider_event_unique',
            );
        });

        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event_type', 150);
            $table->string('aggregate_type', 100);
            $table->uuid('aggregate_id');
            $table->foreignUuid('organisation_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignUuid('provider_id')->nullable()->constrained('integration_providers')->restrictOnDelete();
            $table->unsignedSmallInteger('payload_version')->default(1);
            $table->json('payload');
            $table->uuid('correlation_id');
            $table->timestampTz('occurred_at');
            $table->timestampTz('available_at');
            $table->timestampTz('claimed_at')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('dead_lettered_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('last_error_code', 100)->nullable();
            $table->timestampsTz();

            $table->index(
                ['published_at', 'dead_lettered_at', 'available_at'],
                'outbox_messages_ready_idx',
            );
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
        Schema::dropIfExists('review_eligibility_withdrawals');
        Schema::dropIfExists('review_invitation_schedules');
        Schema::dropIfExists('review_eligibilities');
        Schema::dropIfExists('review_consent_evidence');
        Schema::dropIfExists('protected_reviewer_contacts');
    }
};
