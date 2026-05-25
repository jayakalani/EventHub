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
            Schema::table('events', function (Blueprint $table) {
                $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])
                    ->default('upcoming')
                    ->after('created_by');
            });
        }

        public function down(): void
        {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

};
