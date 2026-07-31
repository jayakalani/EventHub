<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('refunds_allowed')->default(true)->after('cancelled_at');
            $table->unsignedSmallInteger('refund_full_days_before_close')->default(7)->after('refunds_allowed');
            $table->unsignedTinyInteger('refund_full_percentage')->default(100)->after('refund_full_days_before_close');
            $table->unsignedTinyInteger('refund_partial_percentage')->default(75)->after('refund_full_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'refunds_allowed',
                'refund_full_days_before_close',
                'refund_full_percentage',
                'refund_partial_percentage',
            ]);
        });
    }
};
