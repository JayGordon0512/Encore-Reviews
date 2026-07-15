<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->dropUnique('venues_slug_unique');
            $table->unique(['organisation_id', 'slug']);
        });

        Schema::table('performances', function (Blueprint $table): void {
            $table->unique(['provider_source', 'provider_performance_id']);
        });
    }

    public function down(): void
    {
        Schema::table('performances', function (Blueprint $table): void {
            $table->dropUnique(['provider_source', 'provider_performance_id']);
        });

        Schema::table('venues', function (Blueprint $table): void {
            $table->dropUnique(['organisation_id', 'slug']);
            $table->unique('slug');
        });
    }
};
