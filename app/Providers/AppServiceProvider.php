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
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Gate;
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
