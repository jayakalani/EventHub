@php
    $userRole = Auth::user()?->userRole?->name_en;
    $isAttendee = $userRole === \App\Models\UserRole::ATTENDEE;
    $isOrganizer = $userRole === \App\Models\UserRole::ORGANIZER;
    $isAdmin = $userRole === \App\Models\UserRole::ADMIN;
    $isCro = $userRole === \App\Models\UserRole::CRO;
    $user = Auth::user();
    $navInitials = strtoupper(substr($user?->first_name ?? 'U', 0, 1).substr($user?->last_name ?? '', 0, 1));
    $navDisplayName = $user?->full_name ?: ($user?->email ?? 'User');
    $currentLocale = \App\Support\Locale::current();

    $navIdle = 'text-slate-600 transition-all duration-200 ease-out hover:-translate-y-0.5 hover:bg-white/70 hover:text-[#0F0363] hover:shadow-md hover:shadow-[#0F0363]/10';
    $navActive = 'bg-white/80 font-semibold text-[#0F0363] shadow-sm shadow-[#0F0363]/10 ring-1 ring-[#0F0363]/15';
    $iconIdle = 'bg-white/40 text-slate-600 ring-1 ring-white/60 shadow-sm backdrop-blur-md transition-all duration-200 hover:-translate-y-0.5 hover:bg-white/80 hover:text-[#0F0363] hover:shadow-md hover:shadow-[#0F0363]/10 hover:ring-[#0F0363]/20';
    $iconActive = 'bg-white/80 text-[#0F0363] ring-1 ring-[#0F0363]/20 shadow-md shadow-[#0F0363]/10 backdrop-blur-md';

    $adminNavLinks = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'active' => request()->routeIs('dashboard'),
        ],
        [
            'label' => 'Users',
            'route' => 'admin.users',
            'active' => request()->routeIs('admin.users', 'admin.user.*', 'admin.employees.*', 'admin.employee.*'),
        ],
        [
            'label' => 'Categories',
            'route' => 'admin.event-categories',
            'active' => request()->routeIs('admin.event-categories', 'admin.event-categories.*', 'admin.event.category.*'),
        ],
        [
            'label' => 'Reports',
            'route' => 'admin.reports',
            'active' => request()->routeIs('admin.reports', 'admin.reports.*'),
        ],
        [
            'label' => 'Support',
            'route' => 'admin.support-reports',
            'active' => request()->routeIs('admin.support-reports', 'admin.support-reports.*'),
        ],
        [
            'label' => 'Audit Logs',
            'route' => 'admin.audit-logs',
            'active' => request()->routeIs('admin.audit-logs', 'admin.audit-logs.*'),
        ],
    ];
@endphp

<nav x-data="{ open: false }"
    class="sticky top-0 z-50 border-b border-white/40 bg-white/55 shadow-lg shadow-[#0F0363]/5 backdrop-blur-2xl">

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">

            {{-- Brand --}}
            <a href="{{ route('dashboard') }}" class="group flex shrink-0 items-center gap-3 transition-transform duration-200 hover:scale-[1.02]">
                <img src="{{ asset('images/eventhub-logo.png') }}"
                    alt="{{ config('app.name', 'EventHub') }}"
                    class="h-8 w-auto object-contain transition duration-200 group-hover:drop-shadow-md sm:h-9">
                <span class="hidden text-base font-bold tracking-tight text-[#0F0363] transition-colors duration-200 group-hover:text-[#1a0a8a] sm:inline">EventHub</span>
            </a>

            {{-- Desktop links --}}
            <div class="hidden items-center gap-1 lg:flex">
                @if ($isAttendee)
                    <a href="{{ route('attendee.dashboard') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('attendee.dashboard', 'attendee.events.*') ? $navActive : $navIdle }}">
                        {{ t(['en' => 'Events', 'si' => 'ප්‍රසංග']) }}
                    </a>
                    <a href="{{ route('attendee.saved.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('attendee.saved.*') ? $navActive : $navIdle }}">
                        {{ t(['en' => 'Saved', 'si' => 'සුරකින ලද']) }}
                    </a>
                    <a href="{{ route('attendee.hosts.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('attendee.hosts.*') ? $navActive : $navIdle }}">
                        {{ t(['en' => 'Hosts', 'si' => 'සත්කාරකයන්']) }}
                    </a>
                    <a href="{{ route('attendee.bookings.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('attendee.bookings.*') ? $navActive : $navIdle }}">
                        {{ t(['en' => 'Tickets', 'si' => 'ටිකට්']) }}
                    </a>
                    <a href="{{ route('attendee.calendar.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('attendee.calendar.*') ? $navActive : $navIdle }}">
                        {{ t(['en' => 'Calendar', 'si' => 'දින දර්ශනය']) }}
                    </a>
                    <a href="{{ route('attendee.support.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('attendee.support.*') ? $navActive : $navIdle }}">
                        {{ t(['en' => 'Support', 'si' => 'සහාය']) }}
                    </a>
                @elseif ($isOrganizer)
                    <a href="{{ route('dashboard') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard') ? $navActive : $navIdle }}">
                        Dashboard
                    </a>
                    <a href="{{ route('organizer.events.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('organizer.events.*') ? $navActive : $navIdle }}">
                        Events
                    </a>
                    <a href="{{ route('organizer.hosts') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('organizer.hosts', 'organizer.hosts.*', 'organizer.host.*') ? $navActive : $navIdle }}">
                        Hosts
                    </a>
                    <a href="{{ route('organizer.calendar.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('organizer.calendar.*') ? $navActive : $navIdle }}">
                        Calendar
                    </a>
                    <a href="{{ route('organizer.reports') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('organizer.reports', 'organizer.reports.*') ? $navActive : $navIdle }}">
                        Reports
                    </a>
                @elseif ($isAdmin)
                    @foreach ($adminNavLinks as $link)
                        <a href="{{ route($link['route']) }}"
                            class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ $link['active'] ? $navActive : $navIdle }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                @elseif ($isCro)
                    <a href="{{ route('cro.dashboard') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('cro.dashboard') ? $navActive : $navIdle }}">
                        Dashboard
                    </a>
                    <a href="{{ route('cro.inquiries.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('cro.inquiries.*') ? $navActive : $navIdle }}">
                        Inquiries
                    </a>
                    <a href="{{ route('cro.complaints.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('cro.complaints.*') ? $navActive : $navIdle }}">
                        Complaints
                    </a>
                    <a href="{{ route('cro.refund-requests.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('cro.refund-requests.*') ? $navActive : $navIdle }}">
                        Refunds
                    </a>
                    <a href="{{ route('cro.reports') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('cro.reports', 'cro.reports.*') ? $navActive : $navIdle }}">
                        Reports
                    </a>
                @else
                    <a href="{{ route('dashboard') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard') ? $navActive : $navIdle }}">
                        {{ t(['en' => 'Dashboard', 'si' => 'විස්තර පුවරුව']) }}
                    </a>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 sm:gap-3">

                @unless ($isOrganizer || $isCro || $isAdmin)
                    <div class="inline-flex items-center rounded-xl border border-white/50 bg-white/40 p-0.5 text-xs font-semibold shadow-sm backdrop-blur-md transition-all duration-200 hover:bg-white/60"
                        role="group"
                        aria-label="{{ t(['en' => 'Language', 'si' => 'භාෂාව']) }}">
                        @foreach (\App\Support\Locale::SUPPORTED as $locale)
                            <a href="{{ route('locale.switch', $locale) }}"
                                class="rounded-lg px-2.5 py-1.5 transition-all duration-200 {{ $currentLocale === $locale
                                    ? 'bg-[#0F0363] text-white shadow-md shadow-[#0F0363]/25'
                                    : 'text-slate-600 hover:bg-white/80 hover:text-[#0F0363]' }}"
                                hreflang="{{ $locale }}"
                                lang="{{ $locale }}">
                                {{ $locale === 'en' ? 'EN' : 'සිං' }}
                            </a>
                        @endforeach
                    </div>
                @endunless

                @if ($isAttendee)
                    <a href="{{ route('attendee.wallet.index') }}"
                        title="{{ t(['en' => 'Wallet', 'si' => 'පසුම්බිය']) }}"
                        aria-label="{{ t(['en' => 'Wallet', 'si' => 'පසුම්බිය']) }}"
                        class="relative hidden h-10 w-10 items-center justify-center rounded-xl transition sm:flex {{ request()->routeIs('attendee.wallet.*') ? $iconActive : $iconIdle }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </a>

                    <a href="{{ route('attendee.cart.index') }}"
                        title="{{ t(['en' => 'Cart', 'si' => 'Shopping Cart']) }}"
                        aria-label="{{ t(['en' => 'Cart', 'si' => 'Shopping Cart']) }}"
                        class="relative flex h-10 w-10 items-center justify-center rounded-xl transition {{ request()->routeIs('attendee.cart.*') ? $iconActive : $iconIdle }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8L5.4 5M7 13l-1.3 6h11.6L17 13M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
                        </svg>
                        @if (($cartItemCount ?? 0) > 0)
                            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-xs font-bold text-white">
                                {{ $cartItemCount }}
                            </span>
                        @endif
                    </a>
                @endif

                <div class="hidden sm:block">
                    <x-dropdown align="right" width="w-80">
                        <x-slot name="trigger">
                            <button type="button"
                                title="{{ t(['en' => 'Notifications', 'si' => 'දැනුම්දීම්']) }}"
                                aria-label="{{ t(['en' => 'Notifications', 'si' => 'දැනුම්දීම්']) }}"
                                class="relative flex h-10 w-10 items-center justify-center rounded-xl transition {{ request()->routeIs('notifications.*') ? $iconActive : $iconIdle }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                @if (($unreadNotificationCount ?? 0) > 0)
                                    <span class="notification-badge-pulse absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-xs font-bold text-white">
                                        {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                                    </span>
                                @endif
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ t(['en' => 'Notifications', 'si' => 'දැනුම්දීම්']) }}</p>
                                @if (($unreadNotificationCount ?? 0) > 0)
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-[#0F0363] hover:opacity-80">
                                            {{ t(['en' => 'Mark all read', 'si' => 'සියල්ල කියවූ ලෙස']) }}
                                        </button>
                                    </form>
                                @endif
                            </div>

                            @forelse ($recentNotifications ?? [] as $notification)
                                @php
                                    $data = $notification->data;
                                    $isUnread = is_null($notification->read_at);
                                @endphp
                                <a href="{{ $isUnread ? route('notifications.read', $notification->id) : ($data['url'] ?? route('notifications.index')) }}"
                                    class="block border-b border-slate-50 px-4 py-3 transition hover:bg-slate-50 {{ $isUnread ? 'bg-[#0F0363]/5' : '' }}">
                                    <p class="line-clamp-2 text-sm font-medium text-slate-900">{{ $data['message'] ?? t(['en' => 'Notification', 'si' => 'දැනුම්දීම']) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</p>
                                </a>
                            @empty
                                <div class="px-4 py-6 text-center text-sm text-slate-500">
                                    {{ t(['en' => 'No notifications yet.', 'si' => 'තවම දැනුම්දීම් නැත.']) }}
                                </div>
                            @endforelse

                            <div class="px-4 py-3">
                                <a href="{{ route('notifications.index') }}"
                                    class="block text-center text-sm font-semibold text-[#0F0363] hover:opacity-80">
                                    {{ t(['en' => 'View all notifications', 'si' => 'සියලු දැනුම්දීම් බලන්න']) }}
                                </a>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>

                <div class="hidden sm:block">
                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 rounded-2xl border border-white/50 bg-white/40 px-2.5 py-1.5 shadow-sm backdrop-blur-md transition-all duration-200 hover:-translate-y-0.5 hover:border-[#0F0363]/20 hover:bg-white/70 hover:shadow-md hover:shadow-[#0F0363]/10 sm:gap-3 sm:px-3 sm:py-2">
                                @if ($user?->profile_photo)
                                    <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-[#0F0363] p-0.5 shadow-sm shadow-[#0F0363]/25">
                                        <img src="{{ asset('uploads/users-profile-photos/' . $user->profile_photo) }}"
                                            alt="{{ $navDisplayName }}"
                                            class="h-full w-full rounded-full object-cover ring-1 ring-white/30">
                                    </div>
                                @else
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#0F0363] text-sm font-semibold text-white shadow-sm shadow-[#0F0363]/25">
                                        {{ $navInitials }}
                                    </div>
                                @endif
                                <svg class="h-4 w-4 text-slate-400 transition group-hover:text-[#0F0363]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ $navDisplayName }}</p>
                                <p class="text-sm text-slate-500">{{ $user?->email }}</p>
                            </div>

                            <x-dropdown-link :href="route('profile.edit')">
                                {{ t(['en' => 'Profile Settings', 'si' => 'පැතිකඩ සැකසුම්']) }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ t(['en' => 'Sign Out', 'si' => 'ඉවත් වන්න']) }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <button
                    @click="open = !open"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/50 bg-white/40 text-slate-700 shadow-sm backdrop-blur-md transition-all duration-200 hover:-translate-y-0.5 hover:bg-white/70 hover:text-[#0F0363] hover:shadow-md hover:shadow-[#0F0363]/10 lg:hidden"
                    aria-label="{{ t(['en' => 'Toggle menu', 'si' => 'මෙනුව']) }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div x-show="open" x-transition class="border-t border-white/40 bg-white/70 backdrop-blur-2xl lg:hidden" style="display: none;">
        <div class="space-y-1 px-4 py-3">

            @unless ($isOrganizer || $isCro || $isAdmin)
                <div class="flex items-center justify-between px-3 py-2">
                    <span class="text-sm font-medium text-slate-700">{{ t(['en' => 'Language', 'si' => 'භාෂාව']) }}</span>
                    <div class="inline-flex items-center rounded-xl border border-slate-200/80 bg-white/60 p-0.5 text-xs font-semibold">
                        @foreach (\App\Support\Locale::SUPPORTED as $locale)
                            <a href="{{ route('locale.switch', $locale) }}"
                                class="rounded-lg px-2.5 py-1.5 transition {{ $currentLocale === $locale
                                    ? 'bg-[#0F0363] text-white shadow-sm'
                                    : 'text-slate-600 hover:bg-[#0F0363]/5 hover:text-[#0F0363]' }}"
                                hreflang="{{ $locale }}"
                                lang="{{ $locale }}">
                                {{ $locale === 'en' ? 'EN' : 'සිං' }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endunless

            <a href="{{ route('notifications.index') }}"
                class="flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('notifications.*') ? $navActive : 'text-slate-700' }}">
                <span>{{ t(['en' => 'Notifications', 'si' => 'දැනුම්දීම්']) }}</span>
                @if (($unreadNotificationCount ?? 0) > 0)
                    <span class="notification-badge-pulse inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white">
                        {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                    </span>
                @endif
            </a>

            @if ($isAttendee)
                <a href="{{ route('attendee.dashboard') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('attendee.dashboard', 'attendee.events.*') ? $navActive : 'text-slate-700' }}">
                    {{ t(['en' => 'Events', 'si' => 'ප්‍රසංග']) }}
                </a>
                <a href="{{ route('attendee.saved.index') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('attendee.saved.*') ? $navActive : 'text-slate-700' }}">
                    {{ t(['en' => 'Saved', 'si' => 'සුරකින ලද']) }}
                </a>
                <a href="{{ route('attendee.hosts.index') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('attendee.hosts.*') ? $navActive : 'text-slate-700' }}">
                    {{ t(['en' => 'Hosts', 'si' => 'සත්කාරක']) }}
                </a>
                <a href="{{ route('attendee.bookings.index') }}"
                    class="flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('attendee.bookings.*') ? $navActive : 'text-slate-700' }}">
                    <span>{{ t(['en' => 'Tickets', 'si' => 'ටිකට්']) }}</span>
                    @if (($reservedTicketCount ?? 0) > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-500 px-1.5 text-xs font-bold text-white">
                            {{ $reservedTicketCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('attendee.calendar.index') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('attendee.calendar.*') ? $navActive : 'text-slate-700' }}">
                    {{ t(['en' => 'Calendar', 'si' => 'දින දර්ශනය']) }}
                </a>
                <a href="{{ route('attendee.support.index') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('attendee.support.*') ? $navActive : 'text-slate-700' }}">
                    {{ t(['en' => 'Support', 'si' => 'සහාය']) }}
                </a>
                <a href="{{ route('attendee.cart.index') }}"
                    class="flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('attendee.cart.*') ? $navActive : 'text-slate-700' }}">
                    <span>{{ t(['en' => 'Cart', 'si' => 'කරත්තය']) }}</span>
                    @if (($cartItemCount ?? 0) > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white">
                            {{ $cartItemCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('attendee.wallet.index') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('attendee.wallet.*') ? $navActive : 'text-slate-700' }}">
                    {{ t(['en' => 'Wallet', 'si' => 'පසුම්බිය']) }}
                </a>
            @elseif ($isOrganizer)
                <a href="{{ route('dashboard') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('dashboard') ? $navActive : 'text-slate-700' }}">
                    Dashboard
                </a>
                <a href="{{ route('organizer.events.index') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('organizer.events.*') ? $navActive : 'text-slate-700' }}">
                    Events
                </a>
                <a href="{{ route('organizer.hosts') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('organizer.hosts', 'organizer.hosts.*', 'organizer.host.*') ? $navActive : 'text-slate-700' }}">
                    Hosts
                </a>
                <a href="{{ route('organizer.calendar.index') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('organizer.calendar.*') ? $navActive : 'text-slate-700' }}">
                    Calendar
                </a>
                <a href="{{ route('organizer.reports') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('organizer.reports', 'organizer.reports.*') ? $navActive : 'text-slate-700' }}">
                    Reports
                </a>
            @elseif ($isAdmin)
                @foreach ($adminNavLinks as $link)
                    <a href="{{ route($link['route']) }}"
                        class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ $link['active'] ? $navActive : 'text-slate-700' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            @elseif ($isCro)
                <a href="{{ route('cro.dashboard') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('cro.dashboard') ? $navActive : 'text-slate-700' }}">
                    Dashboard
                </a>
                <a href="{{ route('cro.inquiries.index') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('cro.inquiries.*') ? $navActive : 'text-slate-700' }}">
                    Inquiries
                </a>
                <a href="{{ route('cro.complaints.index') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('cro.complaints.*') ? $navActive : 'text-slate-700' }}">
                    Complaints
                </a>
                <a href="{{ route('cro.refund-requests.index') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('cro.refund-requests.*') ? $navActive : 'text-slate-700' }}">
                    Refunds
                </a>
                <a href="{{ route('cro.reports') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('cro.reports', 'cro.reports.*') ? $navActive : 'text-slate-700' }}">
                    Reports
                </a>
            @else
                <a href="{{ route('dashboard') }}"
                    class="block rounded-xl px-3 py-2.5 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363] {{ request()->routeIs('dashboard') ? $navActive : 'text-slate-700' }}">
                    {{ t(['en' => 'Dashboard', 'si' => 'විස්තර පුවරුව']) }}
                </a>
            @endif

            <hr class="border-slate-200">

            <a href="{{ route('profile.edit') }}"
                class="block rounded-xl px-3 py-2.5 text-slate-700 transition hover:bg-[#0F0363]/5 hover:text-[#0F0363]">
                {{ t(['en' => 'Profile Settings', 'si' => 'පැතිකඩ සැකසුම්']) }}
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full rounded-xl px-3 py-2.5 text-left text-red-600 transition hover:bg-red-50">
                    {{ t(['en' => 'Sign Out', 'si' => 'ඉවත් වන්න']) }}
                </button>
            </form>
        </div>
    </div>
</nav>
