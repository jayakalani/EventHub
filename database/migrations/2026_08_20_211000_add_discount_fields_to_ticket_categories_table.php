<?php
/*
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->decimal('discount_price', 10, 2)->nullable()->after('ticket_price');
            $table->dateTime('discount_start')->nullable()->after('booking_end');
            $table->dateTime('discount_end')->nullable()->after('discount_start');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->dropColumn(['discount_price', 'discount_start', 'discount_end']);
        });
    }
};
*/