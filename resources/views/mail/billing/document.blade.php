<!DOCTYPE html>
<html lang="en">
<body style="margin:0;background:#f8fafc;color:#334155;font-family:Arial,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:28px 12px;background:#f8fafc"><tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden">
    <tr><td style="padding:28px;background:#059669;color:#fff;text-align:center"><div style="font-size:22px;font-weight:bold">{{ $clinic->clinic_name ?: 'Aquilizan Dental Clinic' }}</div></td></tr>
    <tr><td style="padding:30px">
        <div style="font-size:13px;color:#059669;font-weight:bold;text-transform:uppercase">{{ $title }}</div>
        <h1 style="margin:7px 0 10px;color:#0f172a;font-size:25px">{{ $invoice->invoice_number }}</h1>
        <p style="line-height:1.6">Hello {{ $invoice->patient?->name }}, your {{ strtolower($title) }} is attached as a PDF.</p>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="8" style="margin-top:20px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px">
            <tr><td>Invoice date</td><td align="right"><strong>{{ $invoice->invoice_date->format('F j, Y') }}</strong></td></tr>
            <tr><td>Total</td><td align="right"><strong>PHP {{ number_format($invoice->total, 2) }}</strong></td></tr>
            <tr><td>Paid</td><td align="right"><strong>PHP {{ number_format($amountPaid, 2) }}</strong></td></tr>
            <tr><td>Balance</td><td align="right"><strong>PHP {{ number_format($balance, 2) }}</strong></td></tr>
        </table>
        @if($clinic->email)
            <p style="margin:22px 0 0;line-height:1.6">For questions or corrections, reply to this email; your message will be sent to <a href="mailto:{{ $clinic->email }}" style="color:#059669">{{ $clinic->email }}</a>.</p>
        @else
            <p style="margin:22px 0 0;line-height:1.6">For questions or corrections, please contact the clinic.</p>
        @endif
    </td></tr>
    <tr><td style="padding:20px;background:#f1f5f9;text-align:center;color:#64748b;font-size:13px;line-height:1.7">
        <strong>{{ $clinic->clinic_name ?: 'Aquilizan Dental Clinic' }}</strong><br>
        @if ($clinic->address){{ $clinic->address }}<br>@endif
        @if ($clinic->phone){{ $clinic->phone }}<br>@endif
        @if ($clinic->email){{ $clinic->email }}@endif
    </td></tr>
</table></td></tr></table>
</body>
</html>
