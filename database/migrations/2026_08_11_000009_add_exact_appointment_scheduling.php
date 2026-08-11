<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('requested_start_at')->nullable()->after('preferred_time_window');
            $table->timestamp('requested_end_at')->nullable()->after('requested_start_at');
            $table->string('scheduling_mode', 20)->default('exact')->after('requested_end_at');
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('scheduling_mode');
            $table->index(['status', 'requested_start_at', 'requested_end_at'], 'appointments_request_range_idx');
        });

        DB::table('appointments')->whereNotNull('scheduled_start_at')->update([
            'requested_start_at' => DB::raw('scheduled_start_at'),
            'requested_end_at' => DB::raw('scheduled_end_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_request_range_idx');
            $table->dropColumn(['requested_start_at', 'requested_end_at', 'scheduling_mode', 'duration_minutes']);
        });
    }
};
