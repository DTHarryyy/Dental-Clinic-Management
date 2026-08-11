YOUR APPOINTMENT IS CONFIRMED

Hello {{ $appointment->full_name }},

Your appointment request with {{ $clinic->clinic_name ?: 'Aquilizan Dental Clinic' }} has been approved.

Reference: APT-{{ str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT) }}
Status: Confirmed
Services: {{ $appointment->service_names }}
@if($appointment->scheduling_mode !== 'first_come')
Duration: {{ $appointment->total_duration_minutes }} minutes
@endif
Estimated total: ₱{{ number_format($appointment->estimated_total, 2) }}
Date: {{ $appointment->appointment_date->format('F j, Y') }}
Time: @if($appointment->scheduling_mode === 'first_come' && $appointment->scheduled_start_at)First come, first served from {{ $appointment->scheduled_start_at->setTimezone('Asia/Manila')->format('g:i A') }}@elseif($appointment->scheduled_start_at && $appointment->scheduled_end_at){{ $appointment->scheduled_start_at->setTimezone('Asia/Manila')->format('g:i A') }}–{{ $appointment->scheduled_end_at->setTimezone('Asia/Manila')->format('g:i A') }}@else{{ $appointment->appointment_time ?: 'Time to be arranged' }}@endif
@if ($appointment->dentist)
Dentist: {{ $appointment->dentist->name }}
@endif

If you need to change or cancel your appointment, please contact the clinic.

{{ $clinic->clinic_name ?: 'Aquilizan Dental Clinic' }}
@if ($clinic->address){{ $clinic->address }}
@endif
@if ($clinic->phone)Phone: {{ $clinic->phone }}
@endif
@if ($clinic->email)Email: {{ $clinic->email }}
@endif
@if ($clinic->website)Website: {{ $clinic->website }}
@endif
