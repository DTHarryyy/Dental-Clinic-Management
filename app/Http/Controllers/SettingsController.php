<?php

namespace App\Http\Controllers;

use App\Models\ClinicSetting;
use App\Models\ClinicBusinessHour;
use App\Models\ClinicClosure;
use App\Models\Faq;
use App\Models\PublicSiteSetting;
use App\Models\PublicTeamProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        return redirect()->route('settings.clinic');
    }

    public function clinic()
    {
        return view('settings.clinic', [
            'clinic' => ClinicSetting::current(),
        ]);
    }

    public function services()
    {
        return view('settings.services', [
            'services' => Service::orderBy('public_sort_order')->orderBy('name')->get(),
        ]);
    }

    public function publicWebsite()
    {
        return view('settings.public-website', [
            'site' => PublicSiteSetting::current(),
        ]);
    }

    public function businessHours()
    {
        return view('settings.business-hours', [
            'clinic' => ClinicSetting::current(),
            'hours' => ClinicBusinessHour::cached(),
        ]);
    }

    public function closures()
    {
        return view('settings.closures', [
            'closures' => ClinicClosure::latest('closure_date')->paginate(12),
        ]);
    }

    public function team()
    {
        return view('settings.team', [
            'profiles' => PublicTeamProfile::with('user:id,name')->orderBy('display_order')->orderBy('name')->get(),
            'dentists' => User::where('role', 'dentist')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function faqs()
    {
        return view('settings.faqs', [
            'faqs' => Faq::orderBy('display_order')->orderBy('question')->get(),
        ]);
    }

    public function updateClinic(Request $request)
    {
        $data = $request->validate([
            'clinic_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'booking_lead_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'booking_horizon_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'slot_interval_minutes' => ['nullable', 'integer', 'min:5', 'max:120'],
        ]);

        ClinicSetting::current()->update($data);
        ClinicSetting::forgetCache();

        return $this->respond($request, redirect()->route('settings.clinic')->with('status', 'Clinic information saved.'));
    }

    public function storeService(Request $request)
    {
        if (! $request->filled('duration_minutes') && preg_match('/\d+/', (string) $request->input('duration'), $match)) $request->merge(['duration_minutes' => (int) $match[0]]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'public_description' => ['nullable', 'string', 'max:1000'],
            'public_sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'show_public_price' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'public_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('public_image')) {
            $data['public_image_path'] = $this->storeImage($request, 'public_image', 'services');
        }

        Service::create([
            ...collect($data)->except('public_image')->all(),
            'duration' => $data['duration_minutes'].' min',
            'show_public_price' => $request->boolean('show_public_price', true),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->respond($request, redirect()->route('settings.services')->with('status', 'Service added.'));
    }

    public function updateService(Request $request, Service $service)
    {
        if (! $request->filled('duration_minutes') && preg_match('/\d+/', (string) $request->input('duration'), $match)) $request->merge(['duration_minutes' => (int) $match[0]]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'public_description' => ['nullable', 'string', 'max:1000'],
            'public_sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'show_public_price' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'public_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_public_image' => ['nullable', 'boolean'],
        ]);

        $payload = [...$data, 'duration' => $data['duration_minutes'].' min'];
        $oldImage = null;

        if ($request->has('show_public_price')) {
            $payload['show_public_price'] = $request->boolean('show_public_price');
        }

        if ($request->has('is_active')) {
            $payload['is_active'] = $request->boolean('is_active');
        }

        if ($request->hasFile('public_image')) {
            $oldImage = $service->public_image_path;
            $payload['public_image_path'] = $this->storeImage($request, 'public_image', 'services');
        } elseif ($request->boolean('remove_public_image')) {
            $oldImage = $service->public_image_path;
            $payload['public_image_path'] = null;
        }

        $service->update(collect($payload)->except(['public_image', 'remove_public_image'])->all());

        if ($oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return $this->respond($request, redirect()->route('settings.services')->with('status', 'Service updated.'));
    }

    public function destroyService(Request $request, Service $service)
    {
        $image = $service->public_image_path;
        $service->delete();

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return $this->respond($request, redirect()->route('settings.services')->with('status', 'Service removed.'));
    }

    public function updatePublicWebsite(Request $request)
    {
        $data = $request->validate([
            'hero_eyebrow' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:1000'],
            'about_heading' => ['nullable', 'string', 'max:255'],
            'about_body' => ['nullable', 'string', 'max:3000'],
            'benefits' => ['nullable', 'string', 'max:2000'],
            'map_embed_url' => ['nullable', 'url', 'max:1000'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'privacy_policy' => ['nullable', 'string', 'max:20000'],
            'terms' => ['nullable', 'string', 'max:20000'],
            'privacy_policy_version' => ['required', 'string', 'max:80'],
            'terms_version' => ['required', 'string', 'max:80'],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_hero_image' => ['nullable', 'boolean'],
            'remove_og_image' => ['nullable', 'boolean'],
        ]);

        $site = PublicSiteSetting::current();
        $data['benefits'] = collect(preg_split('/\R+/', (string) ($data['benefits'] ?? '')))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
        $oldImages = [];

        foreach ([['hero_image', 'hero_image_path', 'remove_hero_image'], ['og_image', 'og_image_path', 'remove_og_image']] as [$input, $column, $remove]) {
            if ($request->hasFile($input)) {
                $oldImages[] = $site->{$column};
                $data[$column] = $this->storeImage($request, $input, 'public-site');
            } elseif ($request->boolean($remove)) {
                $oldImages[] = $site->{$column};
                $data[$column] = null;
            }
        }

        $site->update(collect($data)->except(['hero_image', 'og_image', 'remove_hero_image', 'remove_og_image'])->all());

        collect($oldImages)->filter()->each(fn (string $path) => Storage::disk('public')->delete($path));

        return $this->respond($request, redirect()->route('settings.public')->with('status', 'Public website content saved.'));
    }

    public function updateBusinessHours(Request $request)
    {
        $data = $request->validate([
            'booking_lead_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'booking_horizon_days' => ['required', 'integer', 'min:1', 'max:365'],
            'slot_interval_minutes' => ['required', 'integer', 'min:5', 'max:120'],
            'hours' => ['required', 'array'],
            'hours.*.is_open' => ['nullable', 'boolean'],
            'hours.*.morning_opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.morning_closes_at' => ['nullable', 'date_format:H:i'],
            'hours.*.afternoon_opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.afternoon_closes_at' => ['nullable', 'date_format:H:i'],
        ]);

        ClinicSetting::current()->update([
            'booking_lead_minutes' => $data['booking_lead_minutes'],
            'booking_horizon_days' => $data['booking_horizon_days'],
            'slot_interval_minutes' => $data['slot_interval_minutes'],
        ]);
        ClinicSetting::forgetCache();

        foreach (range(0, 6) as $day) {
            $row = $data['hours'][$day] ?? [];
            ClinicBusinessHour::updateOrCreate(['day_of_week' => $day], [
                'is_open' => (bool) ($row['is_open'] ?? false),
                'morning_opens_at' => $row['morning_opens_at'] ?? null,
                'morning_closes_at' => $row['morning_closes_at'] ?? null,
                'afternoon_opens_at' => $row['afternoon_opens_at'] ?? null,
                'afternoon_closes_at' => $row['afternoon_closes_at'] ?? null,
            ]);
        }

        return $this->respond($request, redirect()->route('settings.hours')->with('status', 'Business hours saved.'));
    }

    public function storeClosure(Request $request)
    {
        $data = $request->validate([
            'closure_date' => ['required', 'date'],
            'is_full_day' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'required_unless:is_full_day,1', 'date_format:H:i'],
            'ends_at' => ['nullable', 'required_unless:is_full_day,1', 'date_format:H:i', 'after:starts_at'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        ClinicClosure::create([...$data, 'is_full_day' => $request->boolean('is_full_day', true)]);

        return $this->respond($request, redirect()->route('settings.closures')->with('status', 'Closure added.'));
    }

    public function destroyClosure(Request $request, ClinicClosure $closure)
    {
        $closure->delete();

        return $this->respond($request, redirect()->route('settings.closures')->with('status', 'Closure removed.'));
    }

    public function storeTeam(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'specialties' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_published' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->storeImage($request, 'photo', 'team');
        }

        PublicTeamProfile::create([...$data, 'is_published' => $request->boolean('is_published')]);

        return $this->respond($request, redirect()->route('settings.team')->with('status', 'Team profile added.'));
    }

    public function updateTeam(Request $request, PublicTeamProfile $profile)
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'specialties' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_published' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        $oldPhoto = null;

        if ($request->hasFile('photo')) {
            $oldPhoto = $profile->photo_path;
            $data['photo_path'] = $this->storeImage($request, 'photo', 'team');
        } elseif ($request->boolean('remove_photo')) {
            $oldPhoto = $profile->photo_path;
            $data['photo_path'] = null;
        }

        $profile->update([...collect($data)->except(['photo', 'remove_photo'])->all(), 'is_published' => $request->boolean('is_published')]);

        if ($oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return $this->respond($request, redirect()->route('settings.team')->with('status', 'Team profile updated.'));
    }

    public function destroyTeam(Request $request, PublicTeamProfile $profile)
    {
        $photo = $profile->photo_path;
        $profile->delete();

        if ($photo) {
            Storage::disk('public')->delete($photo);
        }

        return $this->respond($request, redirect()->route('settings.team')->with('status', 'Team profile removed.'));
    }

    public function storeFaq(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Faq::create([...$data, 'is_active' => $request->boolean('is_active', true)]);

        return $this->respond($request, redirect()->route('settings.faqs')->with('status', 'FAQ added.'));
    }

    public function updateFaq(Request $request, Faq $faq)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $faq->update([...$data, 'is_active' => $request->boolean('is_active')]);

        return $this->respond($request, redirect()->route('settings.faqs')->with('status', 'FAQ updated.'));
    }

    public function destroyFaq(Request $request, Faq $faq)
    {
        $faq->delete();

        return $this->respond($request, redirect()->route('settings.faqs')->with('status', 'FAQ removed.'));
    }

    private function storeImage(Request $request, string $input, string $directory): string
    {
        $file = $request->file($input);
        $name = str()->uuid().'.'.$file->extension();

        return $file->storeAs($directory, $name, 'public');
    }
}
