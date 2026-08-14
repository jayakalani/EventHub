<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropIndexIfExists('hosts', 'hosts_email_unique');
        $this->addUniqueIfMissing('hosts', ['created_by', 'email'], 'hosts_created_by_email_unique');

        // artists was renamed from hosts, so the original unique is still named hosts_email_unique
        $this->dropIndexIfExists('artists', 'hosts_email_unique');
        $this->dropIndexIfExists('artists', 'artists_email_unique');
        $this->addUniqueIfMissing('artists', ['created_by', 'email'], 'artists_created_by_email_unique');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('hosts', 'hosts_created_by_email_unique');
        $this->addUniqueIfMissing('hosts', ['email'], 'hosts_email_unique');

        $this->dropIndexIfExists('artists', 'artists_created_by_email_unique');
        $this->addUniqueIfMissing('artists', ['email'], 'artists_email_unique');
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropUnique($index);
            });
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function addUniqueIfMissing(string $table, array $columns, string $index): void
    {
        if (! $this->indexExists($table, $index)) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
                $blueprint->unique($columns, $index);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select('SHOW INDEX FROM '.$table))
            ->pluck('Key_name')
            ->contains($index);
    }
};
