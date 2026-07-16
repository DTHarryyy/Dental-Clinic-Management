@php
    $isEdit = isset($staffUser) && $staffUser;
    $action = $isEdit ? route('users.update', $staffUser) : route('users.store');
    $nameParts = $isEdit ? explode(' ', $staffUser->name, 2) : ['', ''];
    $firstName = old('first_name', $nameParts[0] ?? '');
    $lastName = old('last_name', $nameParts[1] ?? '');
    $fieldId = $isEdit ? 'edit-'.$staffUser->id : 'create';
@endphp

<form action="{{ $action }}" method="POST" data-ajax-form data-loading-text="{{ $isEdit ? 'Saving...' : 'Creating...' }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="space-y-6">
        {{-- Account info --}}
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-4 pb-3 border-b border-slate-100">Account Information</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ $firstName }}" placeholder="Maria" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                    <input type="text" name="last_name" value="{{ $lastName }}" placeholder="Reyes" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $isEdit ? $staffUser->email : '') }}" placeholder="staff@dentalcare.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
                    <input type="tel" name="phone" value="{{ old('phone', $isEdit ? $staffUser->phone : '') }}" placeholder="09XX-XXX-XXXX" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">License No. <span class="text-xs text-slate-400">(Dentists only)</span></label>
                    <input type="text" name="license_no" value="{{ old('license_no', $isEdit ? $staffUser->license_no : '') }}" placeholder="e.g. 0012345" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        {{ $isEdit ? 'Reset Password' : 'Temporary Password' }} @if (! $isEdit) <span class="text-red-500">*</span> @endif
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="password-{{ $fieldId }}" placeholder="{{ $isEdit ? 'Leave blank to keep current' : 'Min. 8 characters' }}" class="w-full px-4 py-2.5 pr-10 rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 transition" minlength="8" {{ $isEdit ? '' : 'required' }} />
                        <button type="button" onclick="const i=document.getElementById('password-{{ $fieldId }}'); i.type = i.type === 'password' ? 'text' : 'password'; this.querySelector('i').classList.toggle('fa-eye'); this.querySelector('i').classList.toggle('fa-eye-slash');" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                            <i class="fa-solid fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Role & access --}}
        <div>
            <h3 class="font-semibold text-sm text-slate-800 mb-4 pb-3 border-b border-slate-100">Role & Access</h3>
            <div class="space-y-3">
                @php
                    $roleOptions = [
                        ['value' => 'admin',        'label' => 'Admin',        'desc' => 'Full access to all modules including users and settings', 'color' => 'bg-violet-100 text-violet-700'],
                        ['value' => 'dentist',      'label' => 'Dentist',      'desc' => 'Access to patients, dental records, and appointments',     'color' => 'bg-blue-100 text-blue-700'],
                        ['value' => 'receptionist', 'label' => 'Receptionist', 'desc' => 'Access to appointments, patients, and billing',             'color' => 'bg-amber-100 text-amber-700'],
                    ];
                    $currentRole = old('role', $isEdit ? $staffUser->role : 'dentist');
                @endphp
                @foreach ($roleOptions as $r)
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="{{ $r['value'] }}" class="sr-only peer" {{ $currentRole === $r['value'] ? 'checked' : '' }} />
                        <div class="border border-slate-200 rounded-xl p-4 flex items-center gap-4 peer-checked:border-emerald-400 peer-checked:bg-emerald-50 hover:bg-slate-50 transition">
                            <div class="flex-1">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-lg {{ $r['color'] }}">{{ $r['label'] }}</span>
                                <div class="text-xs text-slate-500 mt-1">{{ $r['desc'] }}</div>
                            </div>
                            <div class="h-4 w-4 rounded-full border-2 border-slate-300 peer-checked:border-emerald-500 flex items-center justify-center shrink-0">
                                <div class="h-2 w-2 rounded-full bg-emerald-500 hidden peer-checked:block"></div>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between gap-4 bg-slate-50 rounded-xl p-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                @php $currentStatus = old('status', $isEdit ? $staffUser->status : 'active'); @endphp
                <select name="status" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $currentStatus === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            @if (! $isEdit)
                <p class="text-xs text-blue-700 max-w-xs text-right"><i class="fa-solid fa-circle-info mr-1"></i>Give this temporary password to the staff member directly.</p>
            @endif
        </div>

        @if ($isEdit && $staffUser->id !== auth()->id())
            <div class="bg-red-50 border border-red-100 rounded-xl p-4">
                <h3 class="font-semibold text-sm text-red-700 mb-2">Danger Zone</h3>
                <p class="text-xs text-red-600 mb-3">Removing a user revokes their access immediately.</p>
                <form action="{{ route('users.destroy', $staffUser) }}" method="POST" onsubmit="return confirm('Remove this staff account?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full py-2 rounded-xl border border-red-200 text-red-600 font-semibold text-sm hover:bg-red-100 transition">
                        Remove User
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-slate-100">
        <button type="button" x-on:click="open = false" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
            Cancel
        </button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm transition shadow-sm">
            <i class="fa-solid fa-user-plus mr-1"></i> {{ $isEdit ? 'Save Changes' : 'Create Account' }}
        </button>
    </div>
</form>
