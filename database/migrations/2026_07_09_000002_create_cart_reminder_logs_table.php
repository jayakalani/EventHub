<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reminder_type');
            $table->timestamp('sent_at');
            $table->unique(['cart_item_id', 'user_id', 'reminder_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_reminder_logs');
    }
};
