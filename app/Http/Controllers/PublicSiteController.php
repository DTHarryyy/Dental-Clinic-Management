<?php

namespace App\Http\Controllers;

use App\Models\ClinicBusinessHour;
use App\Models\ClinicClosure;
use App\Models\ClinicSetting;
use App\Models\Faq;
use App\Models\PublicSiteSetting;
use App\Models\PublicTeamProfile;
use App\Models\Service;
use Carbon\CarbonImmutable;

class PublicSiteController extends Controller
{
    public function home()
    {
        $site = PublicSiteSetting::current();

        return view('public.home', [
            'site' => $site,
            'clinic' => ClinicSetting::current(),
            'services' => Service::publicCatalog(),
            'team' => PublicTeamProfile::where('is_published', true)->orderBy('display_order')->orderBy('name')->get(),
            'faqs' => Faq::where('is_active', true)->orderBy('display_order')->orderBy('question')->get(),
            'hours' => ClinicBusinessHour::cached(),
            'closure' => ClinicClosure::whereDate('closure_date', '>=', today())->orderBy('closure_date')->first(),
        ]);
    }

    public function privacy()
    {
        return view('public.policy', [
            'title' => 'Privacy Policy',
            'body' => PublicSiteSetting::current()->privacy_policy,
            'updated' => PublicSiteSetting::current()->updated_at,
        ]);
    }

    public function terms()
    {
        return view('public.policy', [
            'title' => 'Terms of Use',
            'body' => PublicSiteSetting::current()->terms,
            'updated' => PublicSiteSetting::current()->updated_at,
        ]);
    }

    public static function hoursLabel(?ClinicBusinessHour $hour): string
    {
        if (! $hour || ! $hour->is_open) {
            return 'Closed';
        }

        $parts = collect([
            $hour->morning_opens_at && $hour->morning_closes_at
                ? self::time($hour->morning_opens_at).' - '.self::time($hour->morning_closes_at)
                : null,
            $hour->afternoon_opens_at && $hour->afternoon_closes_at
                ? self::time($hour->afternoon_opens_at).' - '.self::time($hour->afternoon_closes_at)
                : null,
        ])->filter();

        return $parts->isEmpty() ? 'Closed' : $parts->join(', ');
    }

    private static function time(string $value): string
    {
        return CarbonImmutable::parse($value)->format('g:i A');
    }
}
