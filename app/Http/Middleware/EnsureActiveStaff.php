<?php

namespace App\Http\Middleware;

use App\Services\SecurityAudit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isActiveStaff()) {
            return $next($request);
        }

        if ($user) {
            app(SecurityAudit::class)->record(
                'session.blocked',
                'denied',
                $user,
                context: ['reason' => $user->roleEnum() ? 'inactive' : 'invalid_role'],
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
            'email' => 'Your account is not authorized to access the staff portal.',
        ]);
    }
}
