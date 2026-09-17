<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLinkedPatient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        // ->patient (the relation accessor, not ->patient()) memoizes on the user instance,
        // so this also warms the cache every downstream patient-portal controller reads —
        // no extra exists() query, and no repeat query for the same relation later.
        $patient = $user?->patient;

        if ($patient?->status === 'active') {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Your patient record is still under review.'], 403);
        }

        return redirect()->route('patient.account-review');
    }
}
