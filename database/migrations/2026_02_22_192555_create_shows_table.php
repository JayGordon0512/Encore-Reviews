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
        Schema::create('shows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->string('genre')->nullable();
            $table->string('primary_image_path')->nullable();
            $table->enum('status', ['upcoming', 'now_playing', 'archived'])->default('upcoming');
            $table->text('ticket_url');
            $table->string('ticket_url_source')->nullable();
            $table->timestamp('ticket_url_last_synced_at')->nullable();
            $table->string('provider_source');
            $table->string('provider_event_id');
            $table->timestamps();

            $table->unique(['provider_source', 'provider_event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shows');
    }
};
