<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecordController extends Controller
{
    public function index(Request $request)
    {
        $records = DentalRecord::query()
            ->with(['patient', 'dentist'])
            ->when($request->search, fn ($q) => $q->where('procedure', 'like', "%{$request->search}%")
                ->orWhereHas('patient', fn ($q2) => $q2
                    ->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")))
            ->when($request->service && $request->service !== 'All Services', fn ($q) => $q->where('procedure', $request->service))
            ->latest('treatment_date')
            ->paginate(10)
            ->withQueryString();

        return view('records.index', [
            'records' => $records,
            'patients' => Patient::dropdown(),
            'dentists' => User::cachedDentists(),
            'services' => Service::cached()->pluck('name'),
        ]);
    }

    public function create()
    {
        return view('records.create', [
            'patients' => Patient::dropdown(),
            'dentists' => User::cachedDentists(),
            'services' => Service::cached()->pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'appointment_id' => [
                'nullable',
                'exists:appointments,id',
                Rule::unique('dental_records', 'appointment_id'),
                fn ($attr, $value, $fail) => Appointment::whereKey($value)->where('status', 'cancelled')->exists()
                    ? $fail('That appointment was cancelled, so it cannot be completed.')
                    : null,
            ],
            'treatment_date' => ['required', 'date'],
            'dentist_id' => ['nullable', 'exists:users,id'],
            'procedure' => ['required', Rule::in(Service::names())],
            'tooth_area' => ['nullable', 'string', 'max:255'],
            'next_appointment_date' => ['nullable', 'date'],
            'clinical_notes' => ['required', 'string'],
            'prescription' => ['nullable', 'string'],
            'treatment_fee' => ['nullable', 'numeric', 'min:0'],
            'create_invoice' => ['nullable', 'boolean'],
        ], [
            'procedure.in' => 'That procedure is no longer available. Please pick one from the list.',
            'appointment_id.unique' => 'This appointment already has a treatment record.',
        ]);

        $createInvoice = $request->boolean('create_invoice');
        unset($data['create_invoice']);

        $record = DB::transaction(function () use ($data, $createInvoice) {
            $record = DentalRecord::create([
                ...$data,
                'treatment_fee' => $data['treatment_fee'] ?? 0,
            ]);

            // Filing the record is what completes the appointment - both land together or
            // neither does, so a completed appointment always has its record to back it up.
            if ($record->appointment_id) {
                Appointment::whereKey($record->appointment_id)->update(['status' => 'completed']);
            }

            if ($createInvoice) {
                $invoice = Invoice::create([
                    'patient_id' => $record->patient_id,
                    'dental_record_id' => $record->id,
                    'invoice_date' => now()->toDateString(),
                    'subtotal' => $record->treatment_fee,
                    'discount' => 0,
                    'total' => $record->treatment_fee,
                    'payment_status' => 'unpaid',
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $record->procedure,
                    'qty' => 1,
                    'price' => $record->treatment_fee,
                ]);
            }

            return $record;
        });

        // Completing from the appointments queue sends you back to the queue (the row now links
        // to its record); a standalone walk-in record has nowhere to return to, so show it.
        $redirect = $record->appointment_id
            ? redirect()->route('appointments.index')->with('status', 'Appointment completed and treatment record saved.')
            : redirect()->route('records.show', $record)->with('status', 'Treatment record saved.');

        return $this->respond($request, $redirect);
    }

    public function show(DentalRecord $record)
    {
        $record->load(['patient', 'dentist', 'invoice']);

        return view('records.show', ['record' => $record]);
    }
}
