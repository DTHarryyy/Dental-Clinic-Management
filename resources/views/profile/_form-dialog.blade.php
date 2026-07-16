@php
    $me = auth()->user();
    $nameParts = explode(' ', $me->name, 2);
    $firstName = old('first_name', $nameParts[0] ?? '');
    $lastName = old('last_name', $nameParts[1] ?? '');
@endphp

<form action="{{ route('profile.update') }}" method="POST" data-ajax-form data-loading-text="Saving...">
    @csrf
    @method('PUT')

    <div class="space-y-6">
        {{-- Account info --}}
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-4 pb-3 border-b border-slate-100">Account Information</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ $firstName }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                    <input type="text" name="last_name" value="{{ $lastName }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $me->email) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
                    <input type="tel" name="phone" value="{{ old('phone', $me->phone) }}" placeholder="09XX-XXX-XXXX" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">License No. <span class="text-xs text-slate-400">(Dentists only)</span></label>
                    <input type="text" name="license_no" value="{{ old('license_no', $me->license_no) }}" placeholder="e.g. 0012345" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
            </div>
        </div>

        {{-- Change password --}}
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-1 pb-3 border-b border-slate-100">Change Password</h3>
            <p class="text-xs text-slate-500 mt-2 mb-4">Leave blank to keep your current password.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Current Password</label>
                    <input type="password" name="current_password" placeholder="Required only if changing password" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">New Password</label>
                    <input type="password" name="password" placeholder="Min. 8 characters" minlength="8" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm New Password</label>
                    <input type="password" name="password_confirmation" placeholder="Repeat new password" minlength="8" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-slate-100">
        <button type="button" x-on:click="open = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
            Cancel
        </button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
            Save Changes
        </button>
    </div>
</form>
