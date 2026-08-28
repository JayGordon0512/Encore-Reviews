<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table): void {
            $table->text('ticket_url')->nullable()->change();
        });

        Schema::create('audience_imports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organisation_id')->constrained()->restrictOnDelete();
            $table->uuid('show_id');
            $table->uuid('performance_id');
            $table->foreignId('imported_by')->constrained('users')->restrictOnDelete();
            $table->string('source_file_name');
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->string('status', 32)->default('completed');
            $table->timestampTz('attendance_confirmed_at');
            $table->uuid('correlation_id');
            $table->timestampsTz();

            $table->foreign(['show_id', 'organisation_id'], 'audience_imports_show_tenant_fk')
                ->references(['id', 'organisation_id'])->on('shows')->restrictOnDelete();
            $table->foreign(['performance_id', 'show_id'], 'audience_imports_performance_show_fk')
                ->references(['id', 'show_id'])->on('performances')->restrictOnDelete();
            $table->unique(
                ['id', 'organisation_id', 'show_id', 'performance_id'],
                'audience_imports_tenant_parent_unique',
            );
            $table->index(['organisation_id', 'created_at'], 'audience_imports_org_created_idx');
        });

        Schema::create('audience_attendances', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organisation_id')->constrained()->restrictOnDelete();
            $table->uuid('show_id');
            $table->uuid('performance_id');
            $table->foreignUuid('contact_id')->constrained('protected_reviewer_contacts')->restrictOnDelete();
            $table->uuid('audience_import_id');
            $table->string('source', 32)->default('organiser_csv');
            $table->string('attendance_state', 32)->default('organiser_confirmed');
            $table->string('status', 32)->default('active');
            $table->timestampsTz();

            $table->foreign(['show_id', 'organisation_id'], 'audience_attendances_show_tenant_fk')
                ->references(['id', 'organisation_id'])->on('shows')->restrictOnDelete();
            $table->foreign(['performance_id', 'show_id'], 'audience_attendances_performance_show_fk')
                ->references(['id', 'show_id'])->on('performances')->restrictOnDelete();
            $table->foreign(
                ['audience_import_id', 'organisation_id', 'show_id', 'performance_id'],
                'audience_attendances_import_tenant_fk',
            )->references(
                ['id', 'organisation_id', 'show_id', 'performance_id'],
            )->on('audience_imports')->restrictOnDelete();
            $table->unique(['performance_id', 'contact_id'], 'audience_attendances_performance_contact_unique');
            $table->index(['organisation_id', 'status'], 'audience_attendances_org_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_attendances');
        Schema::dropIfExists('audience_imports');

        DB::table('shows')->whereNull('ticket_url')->update(['ticket_url' => '']);

        Schema::table('shows', function (Blueprint $table): void {
            $table->text('ticket_url')->nullable(false)->change();
        });
    }
};
