Aquilizan Dental Clinic

@if($event === 'booking_received')
We received your appointment request.
Hello {{ $entity->full_name }}, your request is pending review.
Service: {{ $entity->service }}
Date: {{ $entity->appointment_date->format('F j, Y') }}
Time: {{ $entity->appointment_time ?: 'Time to be arranged' }}
@elseif($event === 'appointment_cancelled')
Your appointment was cancelled.
Hello {{ $entity->full_name }}, your {{ $entity->service_names }} appointment on {{ $entity->appointment_date->format('F j, Y') }} at {{ $entity->appointment_time ?: 'a time to be arranged' }} was completely cancelled.
Reason: {{ $entity->cancellation_reason }}
@if($entity->rescheduledAppointment)
A new pending request was created for {{ $entity->rescheduledAppointment->requested_start_at->setTimezone('Asia/Manila')->format('F j, Y, g:i A') }}–{{ $entity->rescheduledAppointment->requested_end_at->setTimezone('Asia/Manila')->format('g:i A') }}. This range is held pending confirmation.
@else
Contact the clinic if you need another schedule.
@endif
@elseif($event === 'appointment_confirmed')
Your appointment is confirmed.
Hello {{ $entity->full_name }}, your {{ $entity->service_names }} appointment is confirmed.
Date: {{ $entity->scheduled_start_at?->setTimezone('Asia/Manila')->format('F j, Y') ?? $entity->appointment_date->format('F j, Y') }}
Time: {{ $entity->scheduled_start_at ? $entity->scheduled_start_at->setTimezone('Asia/Manila')->format('g:i A').'–'.$entity->scheduled_end_at->setTimezone('Asia/Manila')->format('g:i A') : ($entity->appointment_time ?: 'Time to be arranged') }}
@if($entity->dentist)Dentist: {{ $entity->dentist->name }}@endif
@elseif($event === 'appointment_change_decision')
Your appointment request was updated.
Hello {{ $entity->full_name }}, the clinic reviewed your appointment change request.
Current status: {{ ucfirst($entity->status) }}
Date: {{ $entity->appointment_date->format('F j, Y') }}
Time: {{ $entity->appointment_time ?: 'Time to be arranged' }}
@elseif($event === 'appointment_reminder')
Your appointment is coming up.
Hello {{ $entity->full_name }}, this is a reminder for your {{ $entity->service_names }} appointment.
Date: {{ $entity->scheduled_start_at->setTimezone('Asia/Manila')->format('F j, Y') }}
Time: {{ $entity->scheduled_start_at->setTimezone('Asia/Manila')->format('g:i A') }}@if($entity->scheduled_end_at)–{{ $entity->scheduled_end_at->setTimezone('Asia/Manila')->format('g:i A') }}@endif
@if($entity->dentist)Dentist: {{ $entity->dentist->name }}@endif
Please contact the clinic as soon as possible if you need help with your appointment.
@elseif($event === 'staff_credentials')
Your staff account is ready.
Email: {{ $entity->email }}
Temporary password: {{ $secrets['temporary_password'] }}
Sign in: {{ $secrets['login_url'] }}
Please replace your temporary password after signing in.
@else
Reset your password: {{ $secrets['reset_url'] }}
This single-use link expires in 60 minutes. Ignore this message if you did not request it.
@endif

{{ $clinic->address }}
{{ $clinic->phone }}
{{ $clinic->email }}
