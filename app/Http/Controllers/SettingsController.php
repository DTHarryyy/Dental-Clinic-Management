<?php

namespace App\Http\Controllers;

use App\Models\ClinicSetting;
use App\Models\Service;
use Illuminate\Http\Request;

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
            'services' => Service::cached(),
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
        ]);

        ClinicSetting::current()->update($data);
        ClinicSetting::forgetCache();

        return $this->respond($request, redirect()->route('settings.clinic')->with('status', 'Clinic information saved.'));
    }

    public function storeService(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration' => ['nullable', 'string', 'max:255'],
        ]);

        Service::create($data);

        return $this->respond($request, redirect()->route('settings.services')->with('status', 'Service added.'));
    }

    public function updateService(Request $request, Service $service)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration' => ['nullable', 'string', 'max:255'],
        ]);

        $service->update($data);

        return $this->respond($request, redirect()->route('settings.services')->with('status', 'Service updated.'));
    }

    public function destroyService(Service $service)
    {
        $service->delete();

        return redirect()->route('settings.services')->with('status', 'Service removed.');
    }
}
