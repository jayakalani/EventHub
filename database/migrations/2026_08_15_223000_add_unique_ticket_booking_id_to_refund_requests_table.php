<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateBookingIds = DB::table('refund_requests')
            ->select('ticket_booking_id')
            ->groupBy('ticket_booking_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('ticket_booking_id');

        foreach ($duplicateBookingIds as $bookingId) {
            $keepId = DB::table('refund_requests')
                ->where('ticket_booking_id', $bookingId)
                ->orderByRaw("CASE status
                    WHEN 'approved' THEN 0
                    WHEN 'pending' THEN 1
                    WHEN 'declined' THEN 2
                    ELSE 3
                END")
                ->orderBy('id')
                ->value('id');

            DB::table('refund_requests')
                ->where('ticket_booking_id', $bookingId)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('refund_requests', function (Blueprint $table) {
            $table->unique('ticket_booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropUnique(['ticket_booking_id']);
        });
    }
};
