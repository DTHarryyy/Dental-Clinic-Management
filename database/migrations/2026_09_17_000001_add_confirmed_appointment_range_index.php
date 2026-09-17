<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The availability grid's confirmed-appointments query filters on
    // (status, scheduled_start_at, scheduled_end_at), but the only existing index touching
    // those columns is appointments_schedule_index (dentist_id, scheduled_start_at,
    // scheduled_end_at) - its leading column isn't in the predicate, so it can't be used.
    // This mirrors appointments_request_range_idx, which already serves the equivalent
    // pending-appointments query.
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS appointments_confirmed_range_idx ON appointments (status, scheduled_start_at, scheduled_end_at)');

            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->index(['status', 'scheduled_start_at', 'scheduled_end_at'], 'appointments_confirmed_range_idx');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS appointments_confirmed_range_idx');

            return;
        }

        Schema::table('appointments', fn (Blueprint $table) => $table->dropIndex('appointments_confirmed_range_idx'));
    }
};
