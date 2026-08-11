{{ strtoupper($title) }} {{ $invoice->invoice_number }}

Hello {{ $invoice->patient?->name }},

Your {{ strtolower($title) }} is attached as a PDF.

Invoice date: {{ $invoice->invoice_date->format('F j, Y') }}
Total: PHP {{ number_format($invoice->total, 2) }}
Paid: PHP {{ number_format($amountPaid, 2) }}
Balance: PHP {{ number_format($balance, 2) }}

For questions or corrections, reply to this email or contact the clinic.

{{ $clinic->clinic_name ?: 'Aquilizan Dental Clinic' }}
@if ($clinic->address){{ $clinic->address }}
@endif
@if ($clinic->phone){{ $clinic->phone }}
@endif
@if ($clinic->email){{ $clinic->email }}
@endif
