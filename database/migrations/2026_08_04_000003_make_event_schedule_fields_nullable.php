<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('date')->nullable()->change();
            $table->time('time')->nullable()->change();
            $table->string('place')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
            $table->time('time')->nullable(false)->change();
            $table->string('place')->nullable(false)->change();
        });
    }
};
