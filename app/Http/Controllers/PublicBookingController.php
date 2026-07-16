<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Http\Request;

class PublicBookingController extends Controller
{
    public function create()
    {
        $services = Service::orderBy('name')->pluck('name');

        return view('public.book-appointment', ['services' => $services]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['nullable', 'string'],
            'service' => ['required', 'string', 'max:255'],
            'concern' => ['nullable', 'string'],
        ]);

        $data['status'] = 'pending';

        $appointment = Appointment::create($data);

        return redirect()->route('public.book.success')->with('appointment', $appointment);
    }
}
