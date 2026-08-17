<header class="bg-white border-b border-slate-200 sticky top-0 z-10 print:hidden">
    <div class="px-3 sm:px-5 lg:px-8 py-2.5 sm:py-3.5 flex items-center justify-between gap-3">
        <button type="button" class="touch-target rounded-xl border border-slate-200 text-slate-600 lg:hidden" x-on:click="mobileMenu = true" aria-label="Open navigation"><i class="fa-solid fa-bars"></i></button>

        <x-global-search />

        {{-- Right actions --}}
        <div class="flex items-center gap-3">

            {{-- Appointment notifications --}}
            {{-- data-turbo-permanent: Turbo's morph refresh isn't Alpine-aware and would strip the
                 inline display styles Alpine uses for x-show, so exclude this subtree entirely. --}}
            <div
                id="notification-bell"
                data-turbo-permanent
                class="relative"
                x-data="{
                    notificationsOpen: false,
                    loading: true,
                    loadError: false,
                    unreadCount: 0,
                    notifications: [],
                    async loadNotifications() {
                        try {
                            const response = await fetch(@js(route('notifications.index')), { headers: { Accept: 'application/json' } });
                            if (!response.ok) throw new Error('Unable to load notifications.');
                            const payload = await response.json();
                            this.unreadCount = payload.unread_count;
                            this.notifications = payload.notifications;
                        } catch (error) {
                            this.loadError = true;
                        } finally {
                            this.loading = false;
                        }
                    }
                }"
                x-init="loadNotifications()"
                x-on:click.outside="notificationsOpen = false"
                x-on:keydown.escape.window="notificationsOpen = false"
            >
                <button
                    type="button"
                    class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                    x-on:click="notificationsOpen = ! notificationsOpen"
                    x-bind:aria-expanded="notificationsOpen.toString()"
                    aria-controls="appointment-notifications"
                    x-bind:aria-label="'Appointment notifications' + (unreadCount ? `, ${unreadCount} unread` : '')"
                >
                    <i class="fa-regular fa-bell" aria-hidden="true"></i>
                    <span x-show="unreadCount" x-cloak x-text="unreadCount > 99 ? '99+' : unreadCount" class="absolute -right-1.5 -top-1.5 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"></span>
                </button>

                <div
                    id="appointment-notifications"
                    x-show="notificationsOpen"
                    x-cloak
                    x-transition.origin.top.right
                    class="fixed inset-x-3 top-16 z-30 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-96"
                    role="region"
                    aria-label="Recent appointment notifications"
                >
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">Notifications</h2>
                            <p class="text-xs text-slate-500">Upcoming appointments</p>
                        </div>
                        <form x-show="unreadCount" x-cloak method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500">Mark all as read</button>
                        </form>
                    </div>

                    <div class="max-h-[min(26rem,65vh)] overflow-y-auto">
                        <div x-show="loading" class="px-5 py-8 text-center text-sm text-slate-500">Loading notifications…</div>
                        <div x-show="!loading && loadError" x-cloak class="px-5 py-8 text-center text-sm text-red-600">Notifications could not be loaded. Refresh to try again.</div>
                        <template x-for="notification in notifications" x-bind:key="notification.id">
                            <div class="border-b border-slate-100 p-3 last:border-b-0" x-bind:class="notification.read ? 'bg-white' : 'bg-emerald-50/60'">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" x-bind:class="notification.read ? 'bg-slate-100 text-slate-500' : 'bg-emerald-100 text-emerald-700'">
                                        <i x-bind:class="notification.type === 'appointment_requested' ? 'fa-regular fa-bell' : 'fa-regular fa-calendar-check'" aria-hidden="true"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <a x-bind:href="notification.open_url" class="block rounded focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                            <span x-text="notification.title" class="block text-[11px] font-semibold uppercase tracking-wide text-emerald-600"></span>
                                            <span x-text="notification.patient_name" class="block truncate text-sm font-semibold text-slate-800"></span>
                                            <span x-text="notification.services" class="mt-0.5 block truncate text-xs text-slate-500"></span>
                                            <span x-text="notification.scheduled_at" class="mt-1 block text-xs font-medium text-emerald-700"></span>
                                        </a>
                                        <form x-show="!notification.read" method="POST" x-bind:action="notification.read_url" class="mt-2">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-emerald-700 focus:outline-none focus:underline">Mark as read</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="!loading && !loadError && notifications.length === 0" class="px-5 py-10 text-center">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"><i class="fa-regular fa-bell-slash" aria-hidden="true"></i></span>
                            <p class="mt-3 text-sm font-semibold text-slate-700">No notifications yet</p>
                            <p class="mt-1 text-xs text-slate-500">Upcoming appointment reminders will appear here.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Profile --}}
            <div class="relative">
                <button
                    onclick="this.nextElementSibling.classList.toggle('hidden')"
                    class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition"
                >
                    <div class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center font-bold text-emerald-700 text-sm">
                        {{ auth()->user() ? strtoupper(substr(auth()->user()->name, 0, 2)) : '?' }}
                    </div>
                    <div class="text-right hidden sm:block leading-tight">
                        <div class="font-semibold text-sm">{{ auth()->user()->name ?? 'Guest' }}</div>
                        <div class="text-[10px] text-slate-500">{{ ucfirst(auth()->user()->role ?? '') }}</div>
                    </div>
                    <svg class="h-3.5 w-3.5 text-slate-400 hidden sm:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                {{-- Dropdown --}}
                <div class="hidden absolute right-0 mt-2 w-44 bg-white rounded-2xl border border-slate-200 shadow-lg z-20 py-1 overflow-hidden">
                    <a href="{{ route('profile.show') }}" class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-slate-50 text-slate-700"><i class="fa-solid fa-user w-4 text-center"></i> My Profile</a>
                    <div class="border-t border-slate-100 pt-1"><button type="button" onclick="this.closest('.absolute').classList.add('hidden'); window.dispatchEvent(new CustomEvent('open-dialog', { detail: { id: 'logout-confirm' } }))" class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-red-50 text-red-600"><i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Logout</button></div>
                </div>
            </div>

        </div>
    </div>
</header>
