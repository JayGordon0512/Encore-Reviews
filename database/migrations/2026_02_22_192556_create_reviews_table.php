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
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('performance_id');
            $table->uuid('reviewer_id');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->boolean('would_recommend')->default(false);
            $table->json('tags')->nullable();
            $table->text('content')->nullable();
            $table->boolean('verified')->default(false);
            $table->string('verification_source')->nullable();
            $table->string('moderation_status')->nullable();
            $table->text('moderation_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('edited_until')->nullable();
            $table->string('ip_hash')->nullable();
            $table->string('user_agent_hash')->nullable();
            $table->timestamps();

            $table->foreign('performance_id')->references('id')->on('performances')->cascadeOnDelete();
            $table->foreign('reviewer_id')->references('id')->on('reviewers')->cascadeOnDelete();
            $table->index(['performance_id', 'reviewer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
