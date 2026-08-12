<?php

namespace App\Http\Controllers;

use App\Services\DashboardAnalytics;
use App\Support\AnalyticsDateRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardAnalytics $analytics)
    {
        $input = [
            'period' => $request->query('period', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];
        $validator = Validator::make($input, [
            'period' => ['required', Rule::in(AnalyticsDateRange::PERIODS)],
            'from' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $validator->after(function ($validator) use ($input): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            try {
                AnalyticsDateRange::fromInput($input['period'], $input['from'], $input['to']);
            } catch (\InvalidArgumentException $exception) {
                $validator->errors()->add('to', $exception->getMessage());
            }
        });
        if ($validator->fails()) {
            return redirect()->route('dashboard')->withErrors($validator)->withInput($input);
        }

        $validated = $validator->validated();
        $range = AnalyticsDateRange::fromInput($validated['period'], $validated['from'] ?? null, $validated['to'] ?? null);
        $user = $request->user();
        $dashboard = $analytics->forUser($user, $range);

        return view('dashboard', [
            ...$dashboard,
            'todaysAppointments' => $analytics->todaySchedule($user),
        ]);
    }
}
