<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><style>
body{font-family:DejaVu Sans,sans-serif;color:#334155;font-size:12px;margin:32px}.header{background:#059669;color:white;padding:22px}.title{font-size:25px;font-weight:bold}.muted{color:#64748b}.right{text-align:right}table{width:100%;border-collapse:collapse}.meta{margin:26px 0}.items th{background:#f1f5f9;text-align:left;padding:9px}.items td{padding:10px 9px;border-bottom:1px solid #e2e8f0}.totals{width:48%;margin-left:auto;margin-top:20px}.totals td{padding:6px}.grand{font-size:15px;font-weight:bold;border-top:2px solid #059669}.payments{margin-top:28px}.footer{margin-top:35px;padding-top:15px;border-top:1px solid #e2e8f0;text-align:center;color:#64748b}
</style></head>
<body>
<div class="header"><div class="title">{{ strtoupper($title) }}</div><div>{{ $clinic->clinic_name ?: 'Aquilizan Dental Clinic' }}</div></div>
<table class="meta"><tr><td><strong>{{ $invoice->invoice_number }}</strong><br>Bill to: {{ $invoice->patient?->name ?? 'Unknown patient' }}</td><td class="right">Invoice date: {{ $invoice->invoice_date->format('F j, Y') }}@if($invoice->due_date)<br>Due date: {{ $invoice->due_date->format('F j, Y') }}@endif @if($invoice->dentalRecord?->dentist)<br>Dentist: {{ $invoice->dentalRecord->dentist->name }}@endif</td></tr></table>
<table class="items"><thead><tr><th>Description</th><th class="right">Qty</th><th class="right">Price</th><th class="right">Amount</th></tr></thead><tbody>
@foreach($invoice->items as $item)<tr><td>{{ $item->description }}</td><td class="right">{{ $item->qty }}</td><td class="right">PHP {{ number_format($item->price,2) }}</td><td class="right">PHP {{ number_format($item->qty*$item->price,2) }}</td></tr>@endforeach
</tbody></table>
<table class="totals"><tr><td>Subtotal</td><td class="right">PHP {{ number_format($invoice->subtotal,2) }}</td></tr><tr><td>Discount</td><td class="right">PHP {{ number_format($invoice->discount,2) }}</td></tr><tr><td>Total</td><td class="right">PHP {{ number_format($invoice->total,2) }}</td></tr><tr><td>Paid</td><td class="right">PHP {{ number_format($amountPaid,2) }}</td></tr><tr class="grand"><td>Balance</td><td class="right">PHP {{ number_format($balance,2) }}</td></tr></table>
@if($invoice->payments->isNotEmpty())<div class="payments"><strong>Payments</strong><table class="items"><thead><tr><th>Date</th><th>Method / Reference</th><th class="right">Amount</th></tr></thead><tbody>@foreach($invoice->payments as $payment)<tr><td>{{ $payment->paid_at->format('F j, Y g:i A') }}</td><td>{{ $payment->method }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</td><td class="right">PHP {{ number_format($payment->amount,2) }}</td></tr>@endforeach</tbody></table></div>@endif
@if($invoice->notes)<p><strong>Notes:</strong> {{ $invoice->notes }}</p>@endif
<div class="footer"><strong>{{ $clinic->clinic_name ?: 'Aquilizan Dental Clinic' }}</strong><br>{{ collect([$clinic->address,$clinic->phone,$clinic->email,$clinic->website])->filter()->implode(' · ') }}</div>
</body></html>
