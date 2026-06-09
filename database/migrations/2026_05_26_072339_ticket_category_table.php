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
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                  ->constrained('events')
                  ->onDelete('cascade'); // delete ticket categories if event is deleted

            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('no_of_tickets');
            $table->integer('no_of_available_tickets');
            $table->decimal('ticket_price', 10, 2);
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
        Schema::dropIfExists('ticket_categories');
    }
};

