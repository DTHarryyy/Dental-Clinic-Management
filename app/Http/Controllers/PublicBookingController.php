<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use App\Services\TransactionalEmailDispatcher;
use App\Services\AppointmentScheduler;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PublicBookingController extends Controller
{
    public function create()
    {
        $services = Service::cached();

        return view('public.book-appointment', ['services' => $services]);
    }

    public function availability(Request $request, AppointmentScheduler $scheduler)
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'service_ids' => ['nullable', 'array', 'min:1', 'required_without:duration_minutes'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
            'duration_minutes' => ['nullable', 'integer', 'min:30', 'max:480', 'multiple_of:30', 'required_without:service_ids'],
        ]);
        $duration = isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : (int) Service::whereKey($data['service_ids'])->sum('duration_minutes');

        return response()->json(['duration' => $duration, 'slots' => $scheduler->publicSlots($data['date'], $duration)]);
    }

    public function store(Request $request, TransactionalEmailDispatcher $emails, AppointmentScheduler $scheduler)
    {
        // Accept legacy clients during rollout; the public UI already posts the normalized shape.
        if (! $request->has('preferred_date') && $request->has('appointment_date')) {
            $legacyService = Service::where('name', $request->input('service'))->first();
            $request->merge(['preferred_date' => $request->input('appointment_date'),
                'preferred_time_window' => str_contains(strtolower((string) $request->input('appointment_time')), 'afternoon') ? 'afternoon' : 'morning',
                'service_ids' => $legacyService ? [$legacyService->id] : []]);
        }
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'preferred_date' => ['required', 'date', 'after_or_equal:today'],
            'preferred_time_window' => ['required', Rule::in(['morning', 'afternoon'])],
            'requested_start_at' => ['nullable', 'date'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['required', 'integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)],
            'concern' => ['nullable', 'string'],
        ], [
            'service.in' => 'Please choose a service from the list.',
        ]);

        // Public appointments use email as the only contact method. Keep the legacy
        // non-null database column populated until it can be removed from the schema.
        $services = Service::whereKey($data['service_ids'])->get()->keyBy('id');
        $appointment = DB::transaction(function () use ($data, $services, $scheduler) {
            $duration = (int) $services->sum('duration_minutes');
            $requestedStart = filled($data['requested_start_at'] ?? null) ? $scheduler->parseLocal($data['requested_start_at']) : null;
            if ($requestedStart) {
                $scheduler->holdPublicRange($requestedStart, $duration);
                $data['preferred_date'] = $requestedStart->setTimezone(AppointmentScheduler::TIMEZONE)->toDateString();
                $data['preferred_time_window'] = (int) $requestedStart->setTimezone(AppointmentScheduler::TIMEZONE)->format('H') < 12 ? 'morning' : 'afternoon';
            }
            $appointment = Appointment::create([
                ...collect($data)->except('service_ids')->all(), 'contact_number' => '', 'status' => 'pending',
                'appointment_date' => $data['preferred_date'],
                'appointment_time' => ucfirst($data['preferred_time_window']),
                'service' => $services->first()->name,
                'requested_start_at' => $requestedStart,
                'requested_end_at' => $requestedStart?->addMinutes($duration),
                'duration_minutes' => $duration,
                'scheduling_mode' => 'exact',
            ]);
            foreach ($data['service_ids'] as $order => $id) {
                $service = $services[$id];
                $appointment->serviceItems()->create(['service_id' => $service->id, 'name_snapshot' => $service->name,
                    'price_snapshot' => $service->price, 'duration_minutes_snapshot' => $service->duration_minutes, 'display_order' => $order]);
            }
            return $appointment->load('serviceItems');
        });
        $emails->dispatch('booking_received', $appointment->email, $appointment);

        return redirect()->route('public.book.success')->with('appointment', $appointment);
    }
}
