<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your appointment is confirmed</title>
</head>
<body style="margin:0;background:#f8fafc;color:#334155;font-family:Arial,Helvetica,sans-serif;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">Your appointment with Aquilizan Dental Clinic is confirmed.</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="background:#059669;padding:28px;text-align:center;color:#ffffff;">
                        <img src="{{ asset('images/aquilizan-logo.png') }}" width="72" height="72" alt="Aquilizan Dental Clinic" style="display:block;margin:0 auto 12px;border-radius:14px;background:#ffffff;object-fit:contain;">
                        <div style="font-size:22px;font-weight:700;">{{ $clinic->clinic_name ?: 'Aquilizan Dental Clinic' }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px 28px;">
                        <div style="color:#059669;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;">Confirmed</div>
                        <h1 style="margin:8px 0 12px;color:#0f172a;font-size:26px;line-height:1.25;">Your appointment is confirmed</h1>
                        <p style="margin:0 0 24px;line-height:1.65;">Hello {{ $appointment->full_name }}, your appointment request has been approved. We look forward to seeing you.</p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;padding:8px 18px;">
                            <tr><td style="padding:11px 0;color:#64748b;">Reference</td><td align="right" style="padding:11px 0;color:#0f172a;font-weight:700;">APT-{{ str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT) }}</td></tr>
                            <tr><td style="padding:11px 0;border-top:1px solid #d1fae5;color:#64748b;">Services</td><td align="right" style="padding:11px 0;border-top:1px solid #d1fae5;color:#0f172a;font-weight:600;">{{ $appointment->service_names }}</td></tr>
                            @if($appointment->scheduling_mode !== 'first_come')<tr><td style="padding:11px 0;border-top:1px solid #d1fae5;color:#64748b;">Duration</td><td align="right" style="padding:11px 0;border-top:1px solid #d1fae5;color:#0f172a;font-weight:600;">{{ $appointment->total_duration_minutes }} minutes</td></tr>@endif
                            <tr><td style="padding:11px 0;border-top:1px solid #d1fae5;color:#64748b;">Estimated total</td><td align="right" style="padding:11px 0;border-top:1px solid #d1fae5;color:#0f172a;font-weight:600;">₱{{ number_format($appointment->estimated_total, 2) }}</td></tr>
                            <tr><td style="padding:11px 0;border-top:1px solid #d1fae5;color:#64748b;">Date</td><td align="right" style="padding:11px 0;border-top:1px solid #d1fae5;color:#0f172a;font-weight:600;">{{ $appointment->appointment_date->format('F j, Y') }}</td></tr>
                            <tr><td style="padding:11px 0;border-top:1px solid #d1fae5;color:#64748b;">Time</td><td align="right" style="padding:11px 0;border-top:1px solid #d1fae5;color:#0f172a;font-weight:600;">@if($appointment->scheduling_mode === 'first_come' && $appointment->scheduled_start_at)First come, first served from {{ $appointment->scheduled_start_at->setTimezone('Asia/Manila')->format('g:i A') }}@elseif($appointment->scheduled_start_at && $appointment->scheduled_end_at){{ $appointment->scheduled_start_at->setTimezone('Asia/Manila')->format('g:i A') }}–{{ $appointment->scheduled_end_at->setTimezone('Asia/Manila')->format('g:i A') }}@else{{ $appointment->appointment_time ?: 'Time to be arranged' }}@endif</td></tr>
                            @if ($appointment->dentist)
                                <tr><td style="padding:11px 0;border-top:1px solid #d1fae5;color:#64748b;">Dentist</td><td align="right" style="padding:11px 0;border-top:1px solid #d1fae5;color:#0f172a;font-weight:600;">{{ $appointment->dentist->name }}</td></tr>
                            @endif
                        </table>

                        <p style="margin:24px 0 0;line-height:1.65;">If you need to change or cancel your appointment, please contact the clinic using the details below.</p>
                    </td>
                </tr>
                <tr>
                    <td style="background:#f1f5f9;padding:22px 28px;text-align:center;color:#64748b;font-size:13px;line-height:1.7;">
                        <strong style="color:#334155;">{{ $clinic->clinic_name ?: 'Aquilizan Dental Clinic' }}</strong><br>
                        @if ($clinic->address){{ $clinic->address }}<br>@endif
                        @if ($clinic->phone){{ $clinic->phone }}@if ($clinic->email) &nbsp;·&nbsp; @endif @endif
                        @if ($clinic->email)<a href="mailto:{{ $clinic->email }}" style="color:#059669;">{{ $clinic->email }}</a><br>@endif
                        @if ($clinic->website)<a href="{{ $clinic->website }}" style="color:#059669;">{{ $clinic->website }}</a>@endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
