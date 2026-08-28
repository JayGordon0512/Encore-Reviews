<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_invitation_schedules', function (Blueprint $table): void {
            $table->uuid('correlation_id')->nullable()->after('eligibility_id');
            $table->unsignedSmallInteger('attempts')->default(0)->after('status');
            $table->timestampTz('claimed_at')->nullable()->after('attempts');
            $table->timestampTz('issued_at')->nullable()->after('claimed_at');
            $table->timestampTz('dead_lettered_at')->nullable()->after('issued_at');
            $table->string('last_error_code', 100)->nullable()->after('suppression_reason');
        });

        Schema::table('review_invitations', function (Blueprint $table): void {
            $table->foreignUuid('eligibility_id')->nullable()->after('id')
                ->constrained('review_eligibilities')->restrictOnDelete();
            $table->unsignedSmallInteger('token_version')->default(1)->after('token_hash');
            $table->string('status', 32)->default('issued')->after('token_version');
            $table->timestampTz('revoked_at')->nullable()->after('used_at');
            $table->string('revocation_reason', 64)->nullable()->after('revoked_at');
            $table->index(['eligibility_id', 'status']);
        });

        DB::table('review_invitations')->whereNotNull('sent_at')->update(['status' => 'sent']);
        DB::statement('CREATE UNIQUE INDEX review_invitations_active_eligibility_unique ON review_invitations (eligibility_id) WHERE eligibility_id IS NOT NULL AND revoked_at IS NULL AND used_at IS NULL');

        Schema::create('review_invitation_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('invitation_id')->constrained('review_invitations')->restrictOnDelete();
            $table->foreignUuid('schedule_id')->constrained('review_invitation_schedules')->restrictOnDelete();
            $table->uuid('correlation_id');
            $table->string('channel', 32)->default('email');
            $table->string('status', 32)->default('pending');
            $table->timestampTz('attempted_at')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestampsTz();

            $table->unique('invitation_id');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_invitation_deliveries');
        DB::statement('DROP INDEX IF EXISTS review_invitations_active_eligibility_unique');

        Schema::table('review_invitations', function (Blueprint $table): void {
            $table->dropIndex(['eligibility_id', 'status']);
            $table->dropConstrainedForeignId('eligibility_id');
            $table->dropColumn([
                'token_version', 'status', 'revoked_at', 'revocation_reason',
            ]);
        });

        Schema::table('review_invitation_schedules', function (Blueprint $table): void {
            $table->dropColumn([
                'correlation_id', 'attempts', 'claimed_at', 'issued_at',
                'dead_lettered_at', 'last_error_code',
            ]);
        });
    }
};
