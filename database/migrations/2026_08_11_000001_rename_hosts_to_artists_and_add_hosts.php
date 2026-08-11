<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK so hosts can be renamed to artists.
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['hosted_by']);
        });

        Schema::rename('hosts', 'artists');

        Schema::table('host_likes', function (Blueprint $table) {
            $table->dropForeign(['host_id']);
        });
        Schema::rename('host_likes', 'artist_likes');
        Schema::table('artist_likes', function (Blueprint $table) {
            $table->renameColumn('host_id', 'artist_id');
        });
        Schema::table('artist_likes', function (Blueprint $table) {
            $table->foreign('artist_id')->references('id')->on('artists')->cascadeOnDelete();
        });

        Schema::table('follow_hosts', function (Blueprint $table) {
            $table->dropForeign(['host_id']);
        });
        Schema::rename('follow_hosts', 'follow_artists');
        Schema::table('follow_artists', function (Blueprint $table) {
            $table->renameColumn('host_id', 'artist_id');
        });
        Schema::table('follow_artists', function (Blueprint $table) {
            $table->foreign('artist_id')->references('id')->on('artists')->cascadeOnDelete();
        });

        Schema::create('hosts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('contact_number');
            $table->string('cover')->nullable();
            $table->timestamps();
            $table->string('created_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });

        Schema::create('event_artist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained('artists')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'artist_id']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('host_id')->nullable()->after('name')->constrained('hosts')->nullOnDelete();
        });

        // Preserve prior hosted_by links as event artists.
        $events = DB::table('events')->select('id', 'hosted_by')->whereNotNull('hosted_by')->get();
        $now = now();
        foreach ($events as $event) {
            DB::table('event_artist')->insert([
                'event_id' => $event->id,
                'artist_id' => $event->hosted_by,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('hosted_by');
        });

        Schema::table('event_categories', function (Blueprint $table) {
            $table->boolean('allows_artists')->default(false)->after('is_active');
        });

        DB::table('event_categories')
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%music%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%entertainment%']);
            })
            ->update(['allows_artists' => true]);
    }

    public function down(): void
    {
        Schema::table('event_categories', function (Blueprint $table) {
            $table->dropColumn('allows_artists');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('hosted_by')->nullable()->after('name');
        });

        $pivots = DB::table('event_artist')
            ->select('event_id', 'artist_id')
            ->orderBy('id')
            ->get()
            ->groupBy('event_id');

        foreach ($pivots as $eventId => $rows) {
            DB::table('events')
                ->where('id', $eventId)
                ->update(['hosted_by' => $rows->first()->artist_id]);
        }

        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['host_id']);
            $table->dropColumn('host_id');
        });

        Schema::dropIfExists('event_artist');
        Schema::dropIfExists('hosts');

        Schema::table('follow_artists', function (Blueprint $table) {
            $table->dropForeign(['artist_id']);
        });
        Schema::table('follow_artists', function (Blueprint $table) {
            $table->renameColumn('artist_id', 'host_id');
        });
        Schema::rename('follow_artists', 'follow_hosts');
        Schema::table('follow_hosts', function (Blueprint $table) {
            $table->foreign('host_id')->references('id')->on('artists')->cascadeOnDelete();
        });

        Schema::table('artist_likes', function (Blueprint $table) {
            $table->dropForeign(['artist_id']);
        });
        Schema::table('artist_likes', function (Blueprint $table) {
            $table->renameColumn('artist_id', 'host_id');
        });
        Schema::rename('artist_likes', 'host_likes');
        Schema::table('host_likes', function (Blueprint $table) {
            $table->foreign('host_id')->references('id')->on('artists')->cascadeOnDelete();
        });

        Schema::rename('artists', 'hosts');

        Schema::table('follow_hosts', function (Blueprint $table) {
            $table->dropForeign(['host_id']);
            $table->foreign('host_id')->references('id')->on('hosts')->cascadeOnDelete();
        });
        Schema::table('host_likes', function (Blueprint $table) {
            $table->dropForeign(['host_id']);
            $table->foreign('host_id')->references('id')->on('hosts')->cascadeOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreign('hosted_by')->references('id')->on('hosts')->cascadeOnDelete();
        });
    }
};
