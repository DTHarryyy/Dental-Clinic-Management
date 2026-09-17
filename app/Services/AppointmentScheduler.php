<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ClinicBusinessHour;
use App\Models\ClinicClosure;
use App\Models\ClinicSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentScheduler
{
    public const TIMEZONE = 'Asia/Manila';
    public const WINDOWS = ['morning' => ['08:00', '12:00'], 'afternoon' => ['13:00', '17:00']];

    public function parseLocal(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, self::TIMEZONE)->utc();
    }

    public const NO_DENTIST_MESSAGE = 'No dentist is accepting bookings yet. Please check back later.';

    public function hasActiveDentist(): bool
    {
        return $this->activeDentistIds()->isNotEmpty();
    }

    public function publicSlots(string $date, int $duration, ?int $exceptId = null): Collection
    {
        if (! $this->dateWithinBookingRules($date)) {
            return collect();
        }

        $dentists = $this->activeDentistIds();

        if ($dentists->isEmpty()) {
            return $this->unavailableGrid($date, $duration, self::NO_DENTIST_MESSAGE);
        }

        $dayStart = CarbonImmutable::parse($date, self::TIMEZONE)->startOfDay()->utc();

        return $this->computeSlots($date, $duration, $dentists, $this->loadBetween($dayStart, $dayStart->addDay(), $exceptId));
    }

    public function availableSlots(Appointment $appointment, int $dentistId, string $date, ?int $duration = null): Collection
    {
        $duration ??= $appointment->total_duration_minutes;
        $dayStart = CarbonImmutable::parse($date, self::TIMEZONE)->startOfDay()->utc();
        $dayEnd = $dayStart->addDay();

        $busy = Appointment::query()->where('dentist_id', $dentistId)->where('status', 'confirmed')
            ->whereKeyNot($appointment->id)->whereNotNull('scheduled_start_at')
            ->where('scheduled_start_at', '<', $dayEnd)->where('scheduled_end_at', '>', $dayStart)
            ->get(['scheduled_start_at', 'scheduled_end_at']);

        return $this->slotGrid($date, $duration)->filter(function (array $slot) use ($busy) {
            $start = CarbonImmutable::parse($slot['start']);
            $end = CarbonImmutable::parse($slot['end']);
            return ! $busy->contains(fn ($item) => $item->scheduled_start_at->lt($end) && $item->scheduled_end_at->gt($start));
        })->values();
    }

    /**
     * The seven-day strip plus the slots for the chosen day, built from a single pair of
     * appointment queries spanning the whole week instead of one pair per day. dateSummary()
     * is a thin wrapper around this that keeps the old per-day-only contract for callers that
     * don't need the slots.
     *
     * @return array{days: Collection, selected_date: ?string, slots: Collection}
     */
    public function weekAvailability(string $startDate, int $duration, ?string $preferredDate = null, ?int $exceptId = null): array
    {
        $start = CarbonImmutable::parse($startDate, self::TIMEZONE)->startOfDay();
        $dentists = $this->activeDentistIds();
        $noDentists = $dentists->isEmpty();
        $load = $noDentists
            ? ['confirmed' => collect(), 'pending' => collect()]
            : $this->loadBetween($start->utc(), $start->addDays(7)->utc(), $exceptId);

        $days = collect(range(0, 6))->map(function (int $offset) use ($start, $duration, $dentists, $noDentists, $load): array {
            $date = $start->addDays($offset)->toDateString();
            $closures = $this->closuresForDate($date);
            $isClosed = $this->windowsForDate($date)->isEmpty() || $closures->contains('is_full_day', true);
            // Re-asserted here, not inherited: this method no longer delegates to publicSlots(),
            // which is where the horizon/past-date check used to happen for free.
            $bookable = ! $isClosed && $this->dateWithinBookingRules($date);
            $local = CarbonImmutable::parse($date, self::TIMEZONE);
            $closureReason = $closures->first()?->reason;

            $slots = match (true) {
                ! $bookable => collect(),
                $noDentists => $this->unavailableGrid($date, $duration, self::NO_DENTIST_MESSAGE),
                default => $this->computeSlots($date, $duration, $dentists, $load),
            };

            return [
                'date' => $date,
                'weekday' => $local->format('D'),
                'day' => $local->format('j'),
                'month' => $local->format('M'),
                'open' => $bookable,
                'available' => $slots->contains(fn (array $slot) => $slot['available']),
                'reason' => $closureReason ?: ($isClosed ? 'Closed' : ($noDentists ? self::NO_DENTIST_MESSAGE : null)),
                'slots' => $slots,
            ];
        });

        $selected = $days->first(fn (array $day) => $day['date'] === $preferredDate && $day['open'] && $day['available'])
            ?? $days->first(fn (array $day) => $day['open'] && $day['available']);

        // Each day already carries its own slots — computed above at zero extra query cost —
        // so the client can switch between the seven days with no further network round trip.
        return [
            'days' => $days,
            'selected_date' => $selected['date'] ?? null,
        ];
    }

    public function dateSummary(string $startDate, int $duration): Collection
    {
        return $this->weekAvailability($startDate, $duration)['days'];
    }

    /**
     * Confirmed + pending appointments overlapping [$from, $until) - the only DB touch in the
     * availability path. Called once per request regardless of how many days are rendered.
     *
     * @return array{confirmed: Collection, pending: Collection}
     */
    private function loadBetween(CarbonImmutable $from, CarbonImmutable $until, ?int $exceptId = null): array
    {
        $confirmed = Appointment::query()->where('status', 'confirmed')->whereNotNull('dentist_id')
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->where('scheduled_start_at', '<', $until)->where('scheduled_end_at', '>', $from)
            ->get(['dentist_id', 'scheduled_start_at', 'scheduled_end_at']);

        $pending = Appointment::query()->where('status', 'pending')->whereNotNull('requested_start_at')
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->where('requested_start_at', '<', $until)->where('requested_end_at', '>', $from)
            ->get(['dentist_id', 'requested_start_at', 'requested_end_at']);

        return ['confirmed' => $confirmed, 'pending' => $pending];
    }

    /** Pure - no queries. Builds one day's grid and marks each slot available/unavailable. */
    private function computeSlots(string $date, int $duration, Collection $dentists, array $load): Collection
    {
        ['confirmed' => $confirmed, 'pending' => $pending] = $load;

        return $this->slotGrid($date, $duration)->map(function (array $slot) use ($dentists, $confirmed, $pending) {
            $start = CarbonImmutable::parse($slot['start']);
            $end = CarbonImmutable::parse($slot['end']);
            $overlapsSlot = fn ($appointment, string $startField, string $endField) =>
                $appointment->{$startField}->lt($end) && $appointment->{$endField}->gt($start);

            $busyConfirmed = $confirmed->filter(fn ($appointment) => $overlapsSlot($appointment, 'scheduled_start_at', 'scheduled_end_at'));
            $busyPending = $pending->filter(fn ($appointment) => $overlapsSlot($appointment, 'requested_start_at', 'requested_end_at'));

            // A dentist is "occupied" if they have a confirmed appointment or a pending request
            // with a dentist already assigned (e.g. a dentist booking on their own behalf).
            // A pending request with NO dentist assigned is a generic hold on capacity, not a
            // hold on any one dentist, so it is counted separately rather than merged into the
            // occupied-dentist count - otherwise an assigned dentist's own pending + confirmed
            // rows in the same slot would decrement capacity twice.
            $occupiedDentists = $busyConfirmed->pluck('dentist_id')
                ->merge($busyPending->pluck('dentist_id')->filter())
                ->unique()->count();
            $unassignedHolds = $busyPending->whereNull('dentist_id')->count();

            $slot['available'] = ($dentists->count() - $occupiedDentists - $unassignedHolds) > 0;
            $slot['range_label'] .= $slot['available'] ? '' : ' — Already booked';
            return $slot;
        });
    }

    /** The grid with every slot forced unavailable and a reason appended to its label. */
    private function unavailableGrid(string $date, int $duration, string $reason): Collection
    {
        return $this->slotGrid($date, $duration)->map(function (array $slot) use ($reason) {
            $slot['available'] = false;
            $slot['range_label'] .= ' — '.$reason;
            return $slot;
        });
    }

    private function slotGrid(string $date, int $duration): Collection
    {
        return $this->windowsForDate($date)->flatMap(function ($hours, $window) use ($date, $duration) {
            $cursor = CarbonImmutable::parse("{$date} {$hours[0]}", self::TIMEZONE)->utc();
            $limit = CarbonImmutable::parse("{$date} {$hours[1]}", self::TIMEZONE)->utc();
            $slots = [];
            $interval = $this->slotInterval();
            while ($cursor->addMinutes($duration)->lte($limit)) {
                $end = $cursor->addMinutes($duration);
                if (! $this->rangeBlockedByClosure($date, $cursor, $end) && ! $this->rangeBeforeLeadTime($cursor)) {
                    $slots[] = ['start' => $cursor->toIso8601String(), 'end' => $end->toIso8601String(),
                        'label' => $cursor->setTimezone(self::TIMEZONE)->format('g:i A'),
                        'range_label' => $cursor->setTimezone(self::TIMEZONE)->format('g:i A').'–'.$end->setTimezone(self::TIMEZONE)->format('g:i A'),
                        'window' => $window, 'duration' => $duration, 'available' => true];
                }
                $cursor = $cursor->addMinutes($interval);
            }
            return $slots;
        })->values();
    }

    public function preferenceMatches(Appointment $appointment, CarbonImmutable $start, int $duration): bool
    {
        if ($appointment->requested_start_at) {
            return $appointment->requested_start_at->equalTo($start)
                && $appointment->requested_end_at?->equalTo($start->addMinutes($duration));
        }

        $local = $start->setTimezone(self::TIMEZONE);
        [$from, $to] = self::WINDOWS[$appointment->preferred_time_window] ?? self::WINDOWS['morning'];
        return $appointment->preferred_date?->toDateString() === $local->toDateString()
            && $local->format('H:i') >= $from && $local->format('H:i') < $to;
    }

    public function olderCompetitors(Appointment $appointment): Collection
    {
        if (! $appointment->requested_start_at || ! $appointment->requested_end_at) {
            return collect();
        }

        return Appointment::query()->where('status', 'pending')->whereKeyNot($appointment->id)
            ->where('created_at', '<', $appointment->created_at)
            ->where('requested_start_at', '<', $appointment->requested_end_at)
            ->where('requested_end_at', '>', $appointment->requested_start_at)
            ->orderBy('created_at')->get(['id', 'full_name', 'created_at']);
    }

    public function holdPublicRange(CarbonImmutable $start, int $duration, ?int $exceptId = null, string $field = 'requested_start_at'): void
    {
        $date = $start->setTimezone(self::TIMEZONE)->toDateString();
        $dentistIds = $this->activeDentistIds();

        if ($dentistIds->isEmpty()) {
            throw ValidationException::withMessages([$field => self::NO_DENTIST_MESSAGE]);
        }

        $dentistIds->each(fn ($dentistId) =>
            DB::table('appointment_schedule_locks')->insertOrIgnore(['dentist_id' => $dentistId, 'schedule_date' => $date, 'created_at' => now(), 'updated_at' => now()])
        );
        DB::table('appointment_schedule_locks')->where('schedule_date', $date)->lockForUpdate()->get();
        // Deliberately re-runs publicSlots() fresh here rather than reading any cached grid -
        // this is the double-booking guard, so it must see the database as it is right now.
        $slot = $this->publicSlots($date, $duration, $exceptId)->firstWhere('start', $start->toIso8601String());
        if (! $slot || ! $slot['available']) {
            throw ValidationException::withMessages([$field => 'That time is already booked. Please choose another available time.']);
        }
    }

    public function reserve(Appointment $appointment, int $dentistId, CarbonImmutable $start, ?int $duration, string $mode, ?CarbonImmutable $sessionEnd, ?string $overrideReason): void
    {
        $date = $start->setTimezone(self::TIMEZONE)->toDateString();
        DB::table('appointment_schedule_locks')->insertOrIgnore(['dentist_id' => $dentistId, 'schedule_date' => $date, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('appointment_schedule_locks')->where(['dentist_id' => $dentistId, 'schedule_date' => $date])->lockForUpdate()->first();
        $end = $mode === 'first_come' ? $sessionEnd : $start->addMinutes((int) $duration);

        if (! $end || ! $this->insideClinicWindow($date, $start, $end)) {
            $field = $mode === 'first_come' ? 'session_end_at' : 'scheduled_start_at';
            throw ValidationException::withMessages([$field => 'Choose a start time within clinic hours, on a 30-minute boundary.']);
        }

        $conflicts = Appointment::query()->where('dentist_id', $dentistId)->where('status', 'confirmed')->whereKeyNot($appointment->id)
            ->where('scheduled_start_at', '<', $end)->where('scheduled_end_at', '>', $start);
        if ($mode === 'first_come') {
            $conflicts->where(fn ($query) => $query->where('scheduling_mode', '!=', 'first_come')
                ->orWhere('scheduled_start_at', '!=', $start)->orWhere('scheduled_end_at', '!=', $end));
        }
        if ($conflicts->exists()) {
            throw ValidationException::withMessages(['scheduled_start_at' => 'That dentist is no longer available for this range.']);
        }
        if ($mode === 'exact' && $this->olderCompetitors($appointment)->isNotEmpty() && blank($overrideReason)) {
            throw ValidationException::withMessages(['priority_override_reason' => 'Explain why this newer request is being confirmed before an older request.']);
        }

        $appointment->forceFill(['dentist_id' => $dentistId, 'scheduling_mode' => $mode, 'duration_minutes' => $mode === 'exact' ? $duration : null,
            'scheduled_start_at' => $start, 'scheduled_end_at' => $end, 'confirmed_at' => now(), 'priority_override_reason' => $overrideReason,
            'appointment_date' => $start->setTimezone(self::TIMEZONE)->toDateString(), 'appointment_time' => $start->setTimezone(self::TIMEZONE)->format('g:i A'), 'status' => 'confirmed'])->save();
    }

    private function activeDentistIds(): Collection
    {
        return User::cachedDentists()->pluck('id');
    }

    private function insideClinicWindow(string $date, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $this->windowsForDate($date)->contains(function ($hours) use ($date, $start, $end) {
            $from = CarbonImmutable::parse("{$date} {$hours[0]}", self::TIMEZONE)->utc();
            $to = CarbonImmutable::parse("{$date} {$hours[1]}", self::TIMEZONE)->utc();
            $interval = $this->slotInterval();
            // Only the start needs to land on an interval boundary - slotGrid() only ever
            // produces starts aligned that way. The end is whatever the (arbitrary, summed)
            // service duration makes it; requiring it to also be aligned rejected perfectly
            // valid multi-service bookings whose total wasn't a multiple of the interval.
            return $start->gte($from) && $end->lte($to)
                && $start->minute % $interval === 0
                && $end->gt($start);
        }) && ! $this->rangeBlockedByClosure($date, $start, $end);
    }

    private function windowsForDate(string $date): Collection
    {
        $day = (int) CarbonImmutable::parse($date, self::TIMEZONE)->dayOfWeek;
        $hours = ClinicBusinessHour::cached()->get($day);

        if (! $hours) {
            return collect(self::WINDOWS);
        }

        if (! $hours->is_open) {
            return collect();
        }

        return collect([
            'morning' => $hours->morning_opens_at && $hours->morning_closes_at
                ? [mb_substr($hours->morning_opens_at, 0, 5), mb_substr($hours->morning_closes_at, 0, 5)]
                : null,
            'afternoon' => $hours->afternoon_opens_at && $hours->afternoon_closes_at
                ? [mb_substr($hours->afternoon_opens_at, 0, 5), mb_substr($hours->afternoon_closes_at, 0, 5)]
                : null,
        ])->filter(fn (?array $range) => $range && $range[0] < $range[1]);
    }

    private function closuresForDate(string $date): Collection
    {
        return ClinicClosure::cached()->get($date, collect());
    }

    private function rangeBlockedByClosure(string $date, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $this->closuresForDate($date)->contains(function (ClinicClosure $closure) use ($date, $start, $end): bool {
            if ($closure->is_full_day) {
                return true;
            }

            if (! $closure->starts_at || ! $closure->ends_at) {
                return true;
            }

            $closedStart = CarbonImmutable::parse("{$date} ".mb_substr($closure->starts_at, 0, 5), self::TIMEZONE)->utc();
            $closedEnd = CarbonImmutable::parse("{$date} ".mb_substr($closure->ends_at, 0, 5), self::TIMEZONE)->utc();

            return $start->lt($closedEnd) && $end->gt($closedStart);
        });
    }

    private function dateWithinBookingRules(string $date): bool
    {
        $settings = ClinicSetting::current();
        $localDate = CarbonImmutable::parse($date, self::TIMEZONE)->startOfDay();
        $today = CarbonImmutable::now(self::TIMEZONE)->startOfDay();
        $horizon = $today->addDays($settings->booking_horizon_days ?: 90);

        return $localDate->betweenIncluded($today, $horizon);
    }

    private function rangeBeforeLeadTime(CarbonImmutable $start): bool
    {
        $lead = ClinicSetting::current()->booking_lead_minutes ?: 120;

        return $start->lt(CarbonImmutable::now(self::TIMEZONE)->addMinutes($lead)->utc());
    }

    private function slotInterval(): int
    {
        return max(1, (int) (ClinicSetting::current()->slot_interval_minutes ?: 30));
    }
}
