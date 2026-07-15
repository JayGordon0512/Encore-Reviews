<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('provider');
            $table->string('event_type');
            $table->string('external_event_id');
            $table->char('payload_hash', 64);
            $table->timestampTz('received_at');
            $table->timestampTz('processed_at')->nullable();
            $table->string('status');
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->text('error_message')->nullable();
            $table->uuid('correlation_id')->unique();
            $table->text('response_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->timestampTz('response_expires_at')->nullable();

            $table->unique(['provider', 'external_event_id']);
            $table->index(['status', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_events');
    }
};
