<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organisations', function (Blueprint $table): void {
            $table->string('lifecycle_status', 32)->default('active')->after('is_active');
        });

        Schema::table('shows', function (Blueprint $table): void {
            $table->string('lifecycle_status', 32)->default('upcoming')->after('status');
            $table->boolean('reviews_locked')->default(false)->after('lifecycle_status');
        });

        Schema::create('organisation_user_memberships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organisation_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role', 32);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['organisation_id', 'user_id'], 'organisation_user_membership_unique');
            $table->index(['user_id', 'is_active'], 'organisation_user_membership_user_active_idx');
        });

        Schema::create('integration_user_mappings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('integration_providers')->restrictOnDelete();
            $table->string('account_reference');
            $table->string('external_user_id');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(
                ['provider_id', 'account_reference', 'external_user_id'],
                'integration_user_map_external_unique',
            );
            $table->unique(
                ['provider_id', 'account_reference', 'user_id'],
                'integration_user_map_target_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_user_mappings');
        Schema::dropIfExists('organisation_user_memberships');

        Schema::table('shows', function (Blueprint $table): void {
            $table->dropColumn(['lifecycle_status', 'reviews_locked']);
        });

        Schema::table('organisations', function (Blueprint $table): void {
            $table->dropColumn('lifecycle_status');
        });
    }
};
