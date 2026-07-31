<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_hosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('host_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'host_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_hosts');
    }
};
