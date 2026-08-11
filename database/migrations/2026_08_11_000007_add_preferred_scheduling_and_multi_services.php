<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\CarbonImmutable;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_minutes')->default(30)->after('duration');
            $table->boolean('is_active')->default(true)->after('duration_minutes');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->date('preferred_date')->nullable()->after('appointment_time');
            $table->string('preferred_time_window', 20)->nullable()->after('preferred_date');
            $table->timestamp('scheduled_start_at')->nullable()->after('preferred_time_window');
            $table->timestamp('scheduled_end_at')->nullable()->after('scheduled_start_at');
            $table->timestamp('confirmed_at')->nullable()->after('scheduled_end_at');
            $table->text('priority_override_reason')->nullable()->after('confirmed_at');
            $table->index(['dentist_id', 'scheduled_start_at', 'scheduled_end_at'], 'appointments_schedule_index');
        });

        Schema::create('appointment_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_snapshot');
            $table->decimal('price_snapshot', 10, 2)->default(0);
            $table->unsignedSmallInteger('duration_minutes_snapshot')->default(30);
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['appointment_id', 'service_id']);
        });

        Schema::create('appointment_schedule_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dentist_id')->constrained('users')->cascadeOnDelete();
            $table->date('schedule_date');
            $table->timestamps();
            $table->unique(['dentist_id', 'schedule_date']);
        });

        DB::table('services')->orderBy('id')->each(function ($service) {
            preg_match('/\d+/', (string) $service->duration, $match);
            DB::table('services')->where('id', $service->id)->update([
                'duration_minutes' => max(1, (int) ($match[0] ?? 30)),
            ]);
        });

        DB::table('appointments')->orderBy('id')->each(function ($appointment) {
            $service = DB::table('services')->where('name', $appointment->service)->first();
            $window = str_contains(strtolower((string) $appointment->appointment_time), 'afternoon') ? 'afternoon' : 'morning';
            $schedule = [];
            if ($appointment->dentist_id && preg_match('/^(\d{1,2}):(\d{2})(?:\s*([AP]M))?$/i', trim((string) $appointment->appointment_time), $time)) {
                $hour = (int) $time[1];
                if (isset($time[3])) $hour = ($hour % 12) + (strtoupper($time[3]) === 'PM' ? 12 : 0);
                $start = CarbonImmutable::parse($appointment->appointment_date.' '.sprintf('%02d:%02d', $hour, $time[2]), 'Asia/Manila')->utc();
                $duration = (int) ($service?->duration_minutes ?? 30);
                $schedule = ['scheduled_start_at' => $start, 'scheduled_end_at' => $start->addMinutes($duration)];
                if ($appointment->status === 'confirmed') $schedule['confirmed_at'] = $appointment->updated_at;
            }
            DB::table('appointments')->where('id', $appointment->id)->update([
                'preferred_date' => $appointment->appointment_date,
                'preferred_time_window' => $window,
                ...$schedule,
            ]);
            DB::table('appointment_services')->insert([
                'appointment_id' => $appointment->id,
                'service_id' => $service?->id,
                'name_snapshot' => $appointment->service,
                'price_snapshot' => $service?->price ?? 0,
                'duration_minutes_snapshot' => $service?->duration_minutes ?? 30,
                'display_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_schedule_locks');
        Schema::dropIfExists('appointment_services');
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_schedule_index');
            $table->dropColumn(['preferred_date', 'preferred_time_window', 'scheduled_start_at', 'scheduled_end_at', 'confirmed_at', 'priority_override_reason']);
        });
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn(['duration_minutes', 'is_active']));
    }
};
