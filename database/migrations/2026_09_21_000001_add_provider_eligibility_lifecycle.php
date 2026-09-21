<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_eligibilities', function (Blueprint $table): void {
            $table->timestampTz('verified_at')->nullable()->after('occurred_at');
            $table->timestampTz('revoked_at')->nullable()->after('withdrawn_at');
            $table->string('revocation_reason', 100)->nullable()->after('revoked_at');
            $table->index(['status', 'verified_at'], 'review_eligibilities_verification_idx');
        });

        DB::table('review_eligibilities')
            ->where('status', 'eligible')
            ->update(['status' => 'eligible_pending_attendance']);

        Schema::create('review_eligibility_lifecycle_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('integration_providers')->restrictOnDelete();
            $table->foreignUuid('credential_id')->constrained('integration_credentials')->restrictOnDelete();
            $table->foreignUuid('eligibility_id')->nullable()->constrained('review_eligibilities')->restrictOnDelete();
            $table->string('account_reference');
            $table->uuid('provider_event_id');
            $table->string('provider_booking_id', 100);
            $table->string('event_type', 64);
            $table->string('reason', 100)->nullable();
            $table->unsignedSmallInteger('admission_quantity')->nullable();
            $table->string('provider_show_id', 100)->nullable();
            $table->string('provider_performance_id', 100)->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(
                ['provider_id', 'account_reference', 'provider_event_id'],
                'review_lifecycle_provider_event_unique',
            );
            $table->index(['provider_id', 'account_reference', 'provider_booking_id'], 'review_lifecycle_booking_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_eligibility_lifecycle_events');

        DB::table('review_eligibilities')
            ->whereIn('status', ['eligible_pending_attendance', 'verified_eligible'])
            ->update(['status' => 'eligible']);

        Schema::table('review_eligibilities', function (Blueprint $table): void {
            $table->dropIndex('review_eligibilities_verification_idx');
            $table->dropColumn(['verified_at', 'revoked_at', 'revocation_reason']);
        });
    }
};
