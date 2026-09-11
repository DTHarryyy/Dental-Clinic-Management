<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\AppointmentScheduler;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicBookingController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'))
                ->with('status', 'Please sign in or create a patient account before booking.');
        }

        if ($user->role === 'patient') {
            return redirect()->route($user->patient_id ? 'patient.appointments.create' : 'patient.account-review');
        }

        return redirect()->route('appointments.create');
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

    public function store(Request $request)
    {
        return redirect()->route('public.book')
            ->with('status', 'Please sign in as a verified patient before booking.');
    }
}
