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
        Schema::create('reviewers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email_hash')->nullable();
            $table->string('display_name')->nullable();
            $table->unsignedInteger('trust_score')->default(0);
            $table->timestamps();

            $table->index('email_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviewers');
    }
};
