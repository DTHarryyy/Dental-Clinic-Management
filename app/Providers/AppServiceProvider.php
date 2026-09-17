<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\DentalRecord;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Support\DomainCache;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('patient-registration', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return redirect()->route('register')
                        ->withInput($request->except(['password', 'password_confirmation']))
                        ->withErrors([
                            'email' => 'Too many account-creation attempts. Please wait one minute before trying again.',
                        ])
                        ->withHeaders($headers);
                });
        });

        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn (\App\Models\User $user): bool => $user->hasPermission($permission));
        }

        Vite::useScriptTagAttributes(['data-turbo-track' => 'reload']);
        Vite::useStyleTagAttributes(['data-turbo-track' => 'reload']);

        foreach ([Appointment::class, Patient::class, DentalRecord::class] as $model) {
            $model::saved(fn () => DomainCache::bumpAfterCommit('dashboard', 'reports'));
            $model::deleted(fn () => DomainCache::bumpAfterCommit('dashboard', 'reports'));
        }

        foreach ([Invoice::class, Payment::class] as $model) {
            $model::saved(fn () => DomainCache::bumpAfterCommit('dashboard', 'billing', 'reports'));
            $model::deleted(fn () => DomainCache::bumpAfterCommit('dashboard', 'billing', 'reports'));
        }

        User::saved(fn () => DomainCache::bumpAfterCommit('dashboard', 'reports'));
        User::deleted(fn () => DomainCache::bumpAfterCommit('dashboard', 'reports'));
    }
}
