<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_invitation_deliveries', function (Blueprint $table): void {
            $table->timestampTz('provider_status_at')->nullable()->after('sent_at');
            $table->timestampTz('delivered_at')->nullable()->after('provider_status_at');
            $table->timestampTz('failed_at')->nullable()->after('delivered_at');
            $table->timestampTz('complained_at')->nullable()->after('failed_at');
        });

        Schema::create('mailgun_delivery_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('delivery_id')->nullable()
                ->constrained('review_invitation_deliveries')->restrictOnDelete();
            $table->string('provider_event_id', 255)->unique();
            $table->char('signature_token_digest', 64)->unique();
            $table->string('event_type', 32);
            $table->string('severity', 32)->nullable();
            $table->string('reason_code', 100)->nullable();
            $table->string('outcome', 32);
            $table->timestampTz('event_at');
            $table->timestampTz('received_at');
            $table->timestampsTz();

            $table->index(['delivery_id', 'event_at']);
            $table->index(['outcome', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailgun_delivery_events');

        Schema::table('review_invitation_deliveries', function (Blueprint $table): void {
            $table->dropColumn(['provider_status_at', 'delivered_at', 'failed_at', 'complained_at']);
        });
    }
};
