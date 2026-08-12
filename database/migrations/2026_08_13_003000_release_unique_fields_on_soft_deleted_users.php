<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Free email/nic/google_id on already soft-deleted users so recreates can succeed.
     */
    public function up(): void
    {
        $deletedUsers = DB::table('users')
            ->whereNotNull('deleted_at')
            ->where(function ($query) {
                $query->where('email', 'not like', 'deleted.%')
                    ->orWhere('nic', 'not like', 'deleted.%')
                    ->orWhereNotNull('google_id');
            })
            ->get(['id', 'email', 'nic']);

        foreach ($deletedUsers as $user) {
            $emailPrefix = "deleted.{$user->id}.";
            $nicPrefix = "deleted.{$user->id}.";

            DB::table('users')->where('id', $user->id)->update([
                'email' => str_starts_with((string) $user->email, 'deleted.')
                    ? $user->email
                    : $emailPrefix.substr((string) $user->email, 0, max(0, 255 - strlen($emailPrefix))),
                'nic' => str_starts_with((string) $user->nic, 'deleted.')
                    ? $user->nic
                    : $nicPrefix.substr((string) $user->nic, 0, max(0, 255 - strlen($nicPrefix))),
                'google_id' => null,
                'email_verified_at' => null,
                'is_active' => false,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally irreversible — original email/NIC values are not recoverable safely.
    }
};
