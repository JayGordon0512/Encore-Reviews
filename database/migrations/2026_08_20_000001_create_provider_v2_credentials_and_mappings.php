<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_providers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('integration_credentials', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('integration_providers')->restrictOnDelete();
            $table->string('key_id', 100)->unique();
            $table->string('account_reference');
            $table->string('secret_reference')->unique();
            $table->json('operation_scopes');
            $table->timestampTz('activated_at');
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->uuid('rotated_from_id')->nullable();
            $table->timestampsTz();

            $table->index(
                ['provider_id', 'account_reference'],
                'integration_credentials_provider_account_idx',
            );
            $table->index(
                ['activated_at', 'expires_at', 'revoked_at'],
                'integration_credentials_lifecycle_idx',
            );
        });

        Schema::table('integration_credentials', function (Blueprint $table): void {
            $table->foreign('rotated_from_id', 'integration_credentials_rotation_fk')
                ->references('id')
                ->on('integration_credentials')
                ->nullOnDelete();
        });

        Schema::create('integration_credential_organisations', function (Blueprint $table): void {
            $table->foreignUuid('credential_id')->constrained('integration_credentials')->restrictOnDelete();
            $table->foreignUuid('organisation_id')->constrained()->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(
                ['credential_id', 'organisation_id'],
                'integration_credential_orgs_pk',
            );
        });

        Schema::table('shows', function (Blueprint $table): void {
            $table->unique(['id', 'organisation_id'], 'shows_id_organisation_unique');
        });

        Schema::table('performances', function (Blueprint $table): void {
            $table->unique(['id', 'show_id'], 'performances_id_show_unique');
        });

        Schema::create('integration_organisation_mappings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('provider_id')->constrained('integration_providers')->restrictOnDelete();
            $table->string('account_reference');
            $table->string('external_organisation_id');
            $table->foreignUuid('organisation_id')->constrained()->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(
                ['provider_id', 'account_reference', 'external_organisation_id'],
                'integration_org_map_external_unique',
            );
            $table->unique(
                ['provider_id', 'account_reference', 'organisation_id'],
                'integration_org_map_target_unique',
            );
            $table->unique(
                ['id', 'provider_id', 'account_reference', 'organisation_id'],
                'integration_org_map_parent_unique',
            );
        });

        Schema::create('integration_show_mappings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organisation_mapping_id');
            $table->uuid('provider_id');
            $table->string('account_reference');
            $table->string('external_show_id');
            $table->uuid('organisation_id');
            $table->uuid('show_id');
            $table->timestampsTz();

            $table->foreign(
                ['organisation_mapping_id', 'provider_id', 'account_reference', 'organisation_id'],
                'integration_show_map_org_parent_fk',
            )->references(
                ['id', 'provider_id', 'account_reference', 'organisation_id'],
            )->on('integration_organisation_mappings')->restrictOnDelete();
            $table->foreign(
                ['show_id', 'organisation_id'],
                'integration_show_map_show_tenant_fk',
            )->references(['id', 'organisation_id'])->on('shows')->restrictOnDelete();
            $table->unique(
                ['provider_id', 'account_reference', 'external_show_id'],
                'integration_show_map_external_unique',
            );
            $table->unique(
                ['provider_id', 'account_reference', 'show_id'],
                'integration_show_map_target_unique',
            );
            $table->unique(
                ['id', 'provider_id', 'account_reference', 'organisation_id', 'show_id'],
                'integration_show_map_parent_unique',
            );
        });

        Schema::create('integration_performance_mappings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('show_mapping_id');
            $table->uuid('provider_id');
            $table->string('account_reference');
            $table->string('external_performance_id');
            $table->uuid('organisation_id');
            $table->uuid('show_id');
            $table->uuid('performance_id');
            $table->timestampsTz();

            $table->foreign(
                ['show_mapping_id', 'provider_id', 'account_reference', 'organisation_id', 'show_id'],
                'integration_performance_map_show_parent_fk',
            )->references(
                ['id', 'provider_id', 'account_reference', 'organisation_id', 'show_id'],
            )->on('integration_show_mappings')->restrictOnDelete();
            $table->foreign(
                ['performance_id', 'show_id'],
                'integration_performance_map_performance_fk',
            )->references(['id', 'show_id'])->on('performances')->restrictOnDelete();
            $table->unique(
                ['provider_id', 'account_reference', 'external_performance_id'],
                'integration_performance_map_external_unique',
            );
            $table->unique(
                ['provider_id', 'account_reference', 'performance_id'],
                'integration_performance_map_target_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_performance_mappings');
        Schema::dropIfExists('integration_show_mappings');
        Schema::dropIfExists('integration_organisation_mappings');

        Schema::table('performances', function (Blueprint $table): void {
            $table->dropUnique('performances_id_show_unique');
        });

        Schema::table('shows', function (Blueprint $table): void {
            $table->dropUnique('shows_id_organisation_unique');
        });

        Schema::dropIfExists('integration_credential_organisations');
        Schema::dropIfExists('integration_credentials');
        Schema::dropIfExists('integration_providers');
    }
};
