<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('performances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('show_id');
            $table->uuid('venue_id')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->nullable();
            $table->string('provider_source');
            $table->string('provider_event_id')->nullable();
            $table->string('provider_performance_id')->nullable();
            $table->timestamp('provider_updated_at')->nullable();
            $table->timestamps();

            $table->foreign('show_id')->references('id')->on('shows')->cascadeOnDelete();
            $table->foreign('venue_id')->references('id')->on('venues')->nullOnDelete();
            $table->index(['provider_source', 'provider_event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performances');
    }
};
