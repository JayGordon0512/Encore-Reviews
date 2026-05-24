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
        Schema::create('review_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('performance_id');
            $table->string('email_hash')->nullable();
            $table->string('token_hash')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->string('provider_source')->nullable();
            $table->string('provider_booking_id')->nullable();
            $table->string('provider_ticket_id')->nullable();
            $table->string('attendance_state')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('performance_id')->references('id')->on('performances')->cascadeOnDelete();
            $table->index(['performance_id', 'email_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_invitations');
    }
};
