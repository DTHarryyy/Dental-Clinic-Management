<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Services\SecurityAudit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActivePatient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isActivePatient()) {
            return $next($request);
        }

        if ($user?->roleEnum()?->isStaff()) {
            abort(403);
        }

        if ($user?->roleEnum() === Role::Patient && $user->email_verified_at === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('verify-email')->withErrors([
                'email' => 'Please verify your email address before opening the patient portal.',
            ]);
        }

        if ($user) {
            app(SecurityAudit::class)->record(
                'patient.session.blocked',
                'denied',
                $user,
                context: ['reason' => $user->status === 'active' ? 'invalid_role' : 'inactive'],
                request: $request,
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->route('login')->withErrors([
            'email' => 'Your account cannot access the patient portal.',
        ]);
    }
}
