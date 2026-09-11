<?php

namespace App\Http\Controllers;

use App\Models\DentalRecord;
use Illuminate\Http\Request;

class PatientTreatmentController extends Controller
{
    public function index(Request $request)
    {
        $records = $request->user()->patient->dentalRecords()
            ->whereNotNull('published_at')
            ->with('dentist:id,name')
            ->latest('published_at')
            ->paginate(8);

        return view('patient.treatments.index', compact('records'));
    }

    public function show(Request $request, DentalRecord $record)
    {
        $record = $request->user()->patient->dentalRecords()
            ->whereNotNull('published_at')
            ->with('dentist:id,name')
            ->whereKey($record->id)
            ->firstOrFail();

        return view('patient.treatments.show', compact('record'));
    }
}
