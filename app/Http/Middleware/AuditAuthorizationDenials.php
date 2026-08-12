<?php

namespace App\Http\Middleware;

use App\Services\SecurityAudit;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditAuthorizationDenials
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (AuthorizationException $exception) {
            app(SecurityAudit::class)->authorizationDenied($request);

            throw $exception;
        }
    }
}
