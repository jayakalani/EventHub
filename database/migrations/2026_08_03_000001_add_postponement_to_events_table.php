<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE events MODIFY COLUMN status ENUM('unpublished', 'upcoming', 'ongoing', 'completed', 'cancelled', 'postponed') NOT NULL DEFAULT 'unpublished'");

        Schema::table('events', function (Blueprint $table) {
            $table->text('postponement_reason')->nullable()->after('cancelled_at');
            $table->timestamp('postponed_at')->nullable()->after('postponement_reason');
            $table->boolean('date_tba')->default(false)->after('postponed_at');
        });
    }

    public function down(): void
    {
        DB::table('events')->where('status', 'postponed')->update(['status' => 'upcoming']);

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['postponement_reason', 'postponed_at', 'date_tba']);
        });

        DB::statement("ALTER TABLE events MODIFY COLUMN status ENUM('unpublished', 'upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'unpublished'");
    }
};
