<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_request_nonces', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('credential_id')->constrained('integration_credentials')->restrictOnDelete();
            $table->uuid('nonce');
            $table->timestampTz('request_timestamp');
            $table->timestampTz('received_at');
            $table->timestampTz('expires_at');
            $table->uuid('correlation_id');

            $table->unique(
                ['credential_id', 'nonce'],
                'integration_request_nonces_credential_nonce_unique',
            );
            $table->index('expires_at');
            $table->index('correlation_id');
        });

        Schema::create('integration_idempotency_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('credential_id')->constrained('integration_credentials')->restrictOnDelete();
            $table->string('operation', 100);
            $table->string('idempotency_key');
            $table->char('request_digest', 64);
            $table->string('status', 32);
            $table->string('outcome_type')->nullable();
            $table->uuid('outcome_id')->nullable();
            $table->uuid('first_correlation_id');
            $table->uuid('last_correlation_id');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->timestampsTz();

            $table->unique(
                ['credential_id', 'operation', 'idempotency_key'],
                'integration_idempotency_scope_unique',
            );
            $table->index(
                ['status', 'updated_at'],
                'integration_idempotency_status_updated_idx',
            );
        });

        Schema::create('integration_request_journals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('credential_id')->nullable()->constrained('integration_credentials')->restrictOnDelete();
            $table->foreignUuid('provider_id')->nullable()->constrained('integration_providers')->restrictOnDelete();
            $table->foreignUuid('idempotency_record_id')->nullable()->constrained('integration_idempotency_records')->restrictOnDelete();
            $table->char('credential_key_fingerprint', 64);
            $table->string('operation', 100);
            $table->string('method', 10);
            $table->string('path');
            $table->char('body_digest', 64)->nullable();
            $table->string('auth_outcome', 32);
            $table->string('failure_code', 64)->nullable();
            $table->uuid('correlation_id');
            $table->timestampTz('received_at');
            $table->timestampTz('completed_at')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();

            $table->index(['credential_id', 'received_at'], 'integration_journal_credential_received_idx');
            $table->index(['auth_outcome', 'received_at'], 'integration_journal_outcome_received_idx');
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_request_journals');
        Schema::dropIfExists('integration_idempotency_records');
        Schema::dropIfExists('integration_request_nonces');
    }
};
