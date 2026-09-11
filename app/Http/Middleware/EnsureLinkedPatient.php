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

        if ($user?->patient_id && $user->patient()->where('status', 'active')->exists()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Your patient record is still under review.'], 403);
        }

        return redirect()->route('patient.account-review');
    }
}
