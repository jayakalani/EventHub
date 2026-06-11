<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE events MODIFY COLUMN status ENUM('unpublished', 'upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'unpublished'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('events')->where('status', 'unpublished')->update(['status' => 'upcoming']);

        DB::statement("ALTER TABLE events MODIFY COLUMN status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming'");
    }
};
