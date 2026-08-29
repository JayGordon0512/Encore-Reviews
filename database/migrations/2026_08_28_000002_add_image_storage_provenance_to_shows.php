<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table): void {
            $table->string('primary_image_disk')->nullable()->after('primary_image_path');
            $table->string('primary_image_storage_path')->nullable()->after('primary_image_disk');
        });
    }

    public function down(): void
    {
        Schema::table('shows', function (Blueprint $table): void {
            $table->dropColumn(['primary_image_disk', 'primary_image_storage_path']);
        });
    }
};
