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
        Schema::create('seat_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                  ->constrained('events')
                  ->onDelete('cascade'); // delete seat categories if event is deleted

            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('no_of_seats');
            $table->integer('no_of_available_seats');
            $table->decimal('seat_price', 10, 2);
            $table->string('ticket_color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('booking_start')->nullable();
            $table->dateTime('booking_end')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seat_categories');
    }
};

