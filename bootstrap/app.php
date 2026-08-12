<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('web', [
            \App\Http\Middleware\MeasureRequestPerformance::class,
        ]);

        $middleware->alias([
            'active.staff' => \App\Http\Middleware\EnsureActiveStaff::class,
            'audit.denials' => \App\Http\Middleware\AuditAuthorizationDenials::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Laravel's middleware priority can place `can` outside route middleware.
        // Rendering is therefore the final, reliable audit point for every HTTP denial.
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($exception->getPrevious() instanceof AuthorizationException) {
                app(\App\Services\SecurityAudit::class)->authorizationDenied($request);
            }

            return null;
        });
    })->create();
