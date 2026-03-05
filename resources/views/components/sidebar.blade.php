@php
    $path = request()->path(); // dashboard, patients, etc.
    $is = fn($p) => $path === trim($p, '/');
    $item = function ($href, $label, $icon) use ($is) {
        $active = $is($href);
        $cls = $active
            ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
        return "<a href=\"/$href\" class=\"flex items-center gap-3 px-3 py-2 rounded-xl transition $cls\">
                    <span class=\"text-lg\">$icon</span>
                    <span class=\"font-medium\">$label</span>
                </a>";
    };
@endphp

<aside class="w-72 bg-white border-r min-h-screen flex flex-col">
    {{-- Brand --}}
    <div class="px-5 py-4 border-b">
        <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-2xl bg-emerald-500 flex items-center justify-center text-white font-bold">
                🦷
            </div>
            <div>
                <div class="font-bold leading-tight">DentalCare</div>
                <div class="text-xs text-slate-500">Management System</div>
            </div>
        </div>
    </div>

    {{-- Menu --}}
    <div class="px-3 py-4 space-y-1">
        {!! $item('dashboard', 'Dashboard', '▦') !!}
        {!! $item('patients', 'Patients', '👥') !!}
        {!! $item('appointments', 'Appointments', '📅') !!}
        {!! $item('treatments', 'Treatments', '🩺') !!}
        {!! $item('billing', 'Billing & Payments', '💳') !!}
        {!! $item('inventory', 'Inventory', '📦') !!}
        {!! $item('reports', 'Reports', '📊') !!}
        {!! $item('users', 'Users & Roles', '👤') !!}
        {!! $item('audit', 'Audit Trail', '🧾') !!}
        {!! $item('settings', 'Settings', '⚙️') !!}
    </div>

    {{-- User card bottom --}}
    <div class="mt-auto border-t p-4">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center font-bold text-emerald-700">
                B
            </div>
            <div class="leading-tight">
                <div class="font-semibold">Box</div>
                <div class="text-xs text-slate-500">admin</div>
            </div>
        </div>
    </div>
</aside>