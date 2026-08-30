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
            $table->dropUnique(['eligibility_id']);
            $table->dropForeign(['eligibility_id']);
        });

        Schema::table('review_invitation_schedules', function (Blueprint $table): void {
            $table->uuid('eligibility_id')->nullable()->change();
            $table->foreignUuid('audience_attendance_id')->nullable()
                ->after('eligibility_id')->constrained('audience_attendances')->restrictOnDelete();
            $table->string('source', 32)->default('provider_v2')->after('audience_attendance_id');

            $table->foreign('eligibility_id')->references('id')->on('review_eligibilities')->restrictOnDelete();
            $table->unique('eligibility_id');
            $table->unique('audience_attendance_id', 'review_schedules_audience_attendance_unique');
            $table->index(['source', 'status', 'scheduled_for'], 'review_schedules_source_due_idx');
        });

        Schema::table('review_invitations', function (Blueprint $table): void {
            $table->foreignUuid('audience_attendance_id')->nullable()
                ->after('eligibility_id')->constrained('audience_attendances')->restrictOnDelete();
            $table->index(['audience_attendance_id', 'status'], 'review_invitations_attendance_status_idx');
        });

        // SQLite table reconstruction does not retain a partial index predicate.
        DB::statement('DROP INDEX IF EXISTS review_invitations_active_eligibility_unique');
        DB::statement('CREATE UNIQUE INDEX review_invitations_active_eligibility_unique ON review_invitations (eligibility_id) WHERE eligibility_id IS NOT NULL AND revoked_at IS NULL AND used_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX review_invitations_active_attendance_unique ON review_invitations (audience_attendance_id) WHERE audience_attendance_id IS NOT NULL AND revoked_at IS NULL AND used_at IS NULL');
    }

    public function down(): void
    {
        $manualInvitationIds = DB::table('review_invitations')
            ->whereNotNull('audience_attendance_id')
            ->pluck('id');
        DB::table('review_invitation_deliveries')->whereIn('invitation_id', $manualInvitationIds)->delete();
        DB::table('review_invitations')->whereIn('id', $manualInvitationIds)->delete();
        DB::table('review_invitation_schedules')->whereNotNull('audience_attendance_id')->delete();

        DB::statement('DROP INDEX IF EXISTS review_invitations_active_attendance_unique');

        Schema::table('review_invitations', function (Blueprint $table): void {
            $table->dropIndex('review_invitations_attendance_status_idx');
            $table->dropConstrainedForeignId('audience_attendance_id');
        });

        Schema::table('review_invitation_schedules', function (Blueprint $table): void {
            $table->dropIndex('review_schedules_source_due_idx');
            $table->dropUnique('review_schedules_audience_attendance_unique');
            $table->dropConstrainedForeignId('audience_attendance_id');
            $table->dropColumn('source');
            $table->dropUnique(['eligibility_id']);
            $table->dropForeign(['eligibility_id']);
        });

        Schema::table('review_invitation_schedules', function (Blueprint $table): void {
            $table->uuid('eligibility_id')->nullable(false)->change();
            $table->foreign('eligibility_id')->references('id')->on('review_eligibilities')->restrictOnDelete();
            $table->unique('eligibility_id');
        });

        DB::statement('DROP INDEX IF EXISTS review_invitations_active_eligibility_unique');
        DB::statement('CREATE UNIQUE INDEX review_invitations_active_eligibility_unique ON review_invitations (eligibility_id) WHERE eligibility_id IS NOT NULL AND revoked_at IS NULL AND used_at IS NULL');
    }
};
