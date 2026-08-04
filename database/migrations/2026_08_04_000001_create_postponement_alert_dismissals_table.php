<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postponement_alert_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->timestamp('postponed_at');
            $table->timestamps();

            $table->unique(['user_id', 'event_id', 'postponed_at'], 'postponement_alert_dismissals_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postponement_alert_dismissals');
    }
};
