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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // event title
            $table->foreignId('hosted_by')->constrained('hosts')->onDelete('cascade'); // link to hosts table
            $table->foreignId('category_id')->constrained('event_categories')->onDelete('cascade'); // link to event_categories
            $table->date('date'); // event date
            $table->time('time'); // event time
            $table->string('place'); // venue/place
            $table->integer('no_of_tickets')->nullable(); // optional ticket count
            $table->text('description')->nullable(); // event description
            $table->foreignId('contact_person')->constrained('users')->onDelete('cascade'); // contact person
            $table->string('cover')->nullable(); // cover image
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // organizer
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
