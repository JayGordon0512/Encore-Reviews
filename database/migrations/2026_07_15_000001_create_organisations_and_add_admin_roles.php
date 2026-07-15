<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('support_email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignUuid('organisation_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role')->default('customer_admin')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->index(['organisation_id', 'is_active']);
        });

        Schema::table('shows', function (Blueprint $table): void {
            $table->foreignUuid('organisation_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['organisation_id', 'status']);
        });

        Schema::table('venues', function (Blueprint $table): void {
            $table->foreignUuid('organisation_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['organisation_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organisation_id');
        });

        Schema::table('shows', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organisation_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organisation_id');
            $table->dropColumn(['role', 'is_active']);
        });

        Schema::dropIfExists('organisations');
    }
};
