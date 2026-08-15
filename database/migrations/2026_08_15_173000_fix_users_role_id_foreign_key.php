<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align users.role_id with user_roles.id and prevent orphaned roles.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('role_id')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('user_roles')
                    ->whereColumn('user_roles.id', 'users.role_id');
            })
            ->update(['role_id' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')
                ->references('id')
                ->on('user_roles')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('role_id')->nullable()->change();
        });
    }
};
