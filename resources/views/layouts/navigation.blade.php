@php
    $userRole = Auth::user()?->userRole?->name_en;
    $isAttendee = $userRole === \App\Models\UserRole::ATTENDEE;
    $user = Auth::user();
    $currentLocale = \App\Support\Locale::current();

    $navIdle = 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
    $navActive = 'bg-[#0F0363]/10 text-[#0F0363]';
    $iconIdle = 'bg-slate-100 text-slate-700 hover:bg-slate-200';
    $iconActive = 'bg-[#0F0363]/10 text-[#0F0363]';
@endphp

<nav x-data="{ open: false }"
    class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 shadow-sm backdrop-blur-lg">

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">

            {{-- Brand --}}
            <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0F0363] text-white shadow-md shadow-[#0F0363]/25">
                    <x-application-logo class="h-6 w-6 fill-current" />
                </div>
                <div class="hidden md:block">
                    <p class="text-lg font-bold text-slate-900">EventHub</p>
                    <p class="-mt-1 text-xs text-slate-500">Event Management Platform</p>
                </div>
            </a>

            {{-- Desktop links --}}
            <div class="hidden items-center gap-1 lg:flex">
                @if ($isAttendee)
                    <a href="{{ route('attendee.dashboard') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('attendee.dashboard', 'attendee.events.*') ? $navActive : $navIdle }}">
                        Events
                    </a>
                    <a href="{{ route('attendee.hosts.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('attendee.hosts.*') ? $navActive : $navIdle }}">
                        Hosts
                    </a>
                    <a href="{{ route('attendee.bookings.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('attendee.bookings.*') ? $navActive : $navIdle }}">
                        Tickets
                    </a>
                    <a href="{{ route('attendee.calendar.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('attendee.calendar.*') ? $navActive : $navIdle }}">
                        Calendar
                    </a>
                    <a href="{{ route('attendee.support.index') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('attendee.support.*') ? $navActive : $navIdle }}">
                        Support
                    </a>
                @else
                    <a href="{{ route('attendee.dashboard') }}"
                        class="rounded-xl px-3.5 py-2 text-sm font-medium transition {{ request()->routeIs('attendee.dashboard', 'attendee.events.*') ? $navActive : $navIdle }}">
                        Events
                    </a>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 sm:gap-3">

                <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white p-0.5 text-xs font-semibold"
                    role="group"
                    aria-label="{{ t(['en' => 'Language', 'si' => 'භාෂාව']) }}">
                    @foreach (\App\Support\Locale::SUPPORTED as $locale)
                        <a href="{{ route('locale.switch', $locale) }}"
                            class="rounded-lg px-2.5 py-1.5 transition {{ $currentLocale === $locale
                                ? 'bg-[#0F0363] text-white shadow-sm'
                                : 'text-slate-600 hover:bg-slate-50' }}"
                            hreflang="{{ $locale }}"
                            lang="{{ $locale }}">
                            {{ $locale === 'en' ? 'EN' : 'සිං' }}
                        </a>
                    @endforeach
                </div>

                @if ($isAttendee)
                    <a href="{{ route('attendee.wallet.index') }}"
                        title="Wallet"
                        aria-label="Wallet"
                        class="relative hidden h-10 w-10 items-center justify-center rounded-xl transition sm:flex {{ request()->routeIs('attendee.wallet.*') ? $iconActive : $iconIdle }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </a>

                    <a href="{{ route('attendee.cart.index') }}"
                        title="Cart"
                        aria-label="Cart"
                        class="relative hidden h-10 w-10 items-center justify-center rounded-xl transition sm:flex {{ request()->routeIs('attendee.cart.*') ? $iconActive : $iconIdle }}">
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
                                title="Notifications"
                                aria-label="Notifications"
                                class="relative flex h-10 w-10 items-center justify-center rounded-xl transition {{ request()->routeIs('notifications.*') ? $iconActive : $iconIdle }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                @if (($unreadNotificationCount ?? 0) > 0)
                                    <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-xs font-bold text-white">
                                        {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                                    </span>
                                @endif
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <p class="font-semibold text-slate-900">Notifications</p>
                                @if (($unreadNotificationCount ?? 0) > 0)
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-[#0F0363] hover:opacity-80">
                                            Mark all read
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
                                    <p class="line-clamp-2 text-sm font-medium text-slate-900">{{ $data['message'] ?? 'Notification' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</p>
                                </a>
                            @empty
                                <div class="px-4 py-6 text-center text-sm text-slate-500">
                                    No notifications yet.
                                </div>
                            @endforelse

                            <div class="px-4 py-3">
                                <a href="{{ route('notifications.index') }}"
                                    class="block text-center text-sm font-semibold text-[#0F0363] hover:opacity-80">
                                    View all notifications
                                </a>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>

                <div class="hidden sm:block">
                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-2.5 py-1.5 transition hover:bg-slate-50 sm:gap-3 sm:px-3 sm:py-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#0F0363] text-sm font-semibold text-white">
                                    {{ strtoupper(substr($user?->name ?? 'G', 0, 1)) }}
                                </div>
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ $user?->name }}</p>
                                <p class="text-sm text-slate-500">{{ $user?->email }}</p>
                            </div>

                            <x-dropdown-link :href="route('profile.edit')">
                                Profile Settings
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    Sign Out
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <button
                    @click="open = !open"
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 lg:hidden"
                    aria-label="Toggle menu">
                    <svg class="h-6 w-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div x-show="open" x-transition class="border-t border-slate-200 bg-white lg:hidden" style="display: none;">
        <div class="space-y-1 px-4 py-3">

            <div class="flex items-center justify-between px-3 py-2">
                <span class="text-sm font-medium text-slate-700">{{ t(['en' => 'Language', 'si' => 'භාෂාව']) }}</span>
                <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white p-0.5 text-xs font-semibold">
                    @foreach (\App\Support\Locale::SUPPORTED as $locale)
                        <a href="{{ route('locale.switch', $locale) }}"
                            class="rounded-lg px-2.5 py-1.5 transition {{ $currentLocale === $locale
                                ? 'bg-[#0F0363] text-white shadow-sm'
                                : 'text-slate-600 hover:bg-slate-50' }}"
                            hreflang="{{ $locale }}"
                            lang="{{ $locale }}">
                            {{ $locale === 'en' ? 'EN' : 'සිං' }}
                        </a>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('notifications.index') }}"
                class="flex items-center justify-between rounded-xl px-3 py-2.5 hover:bg-slate-100 {{ request()->routeIs('notifications.*') ? $navActive : '' }}">
                <span>Notifications</span>
                @if (($unreadNotificationCount ?? 0) > 0)
                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white">
                        {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                    </span>
                @endif
            </a>

            @if ($isAttendee)
                <a href="{{ route('attendee.dashboard') }}"
                    class="block rounded-xl px-3 py-2.5 hover:bg-slate-100 {{ request()->routeIs('attendee.dashboard', 'attendee.events.*') ? $navActive : '' }}">
                    Events
                </a>
                <a href="{{ route('attendee.hosts.index') }}"
                    class="block rounded-xl px-3 py-2.5 hover:bg-slate-100 {{ request()->routeIs('attendee.hosts.*') ? $navActive : '' }}">
                    Hosts
                </a>
                <a href="{{ route('attendee.bookings.index') }}"
                    class="flex items-center justify-between rounded-xl px-3 py-2.5 hover:bg-slate-100 {{ request()->routeIs('attendee.bookings.*') ? $navActive : '' }}">
                    <span>Tickets</span>
                    @if (($reservedTicketCount ?? 0) > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-500 px-1.5 text-xs font-bold text-white">
                            {{ $reservedTicketCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('attendee.calendar.index') }}"
                    class="block rounded-xl px-3 py-2.5 hover:bg-slate-100 {{ request()->routeIs('attendee.calendar.*') ? $navActive : '' }}">
                    Calendar
                </a>
                <a href="{{ route('attendee.support.index') }}"
                    class="block rounded-xl px-3 py-2.5 hover:bg-slate-100 {{ request()->routeIs('attendee.support.*') ? $navActive : '' }}">
                    Support
                </a>
                <a href="{{ route('attendee.cart.index') }}"
                    class="flex items-center justify-between rounded-xl px-3 py-2.5 hover:bg-slate-100 {{ request()->routeIs('attendee.cart.*') ? $navActive : '' }}">
                    <span>Cart</span>
                    @if (($cartItemCount ?? 0) > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white">
                            {{ $cartItemCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('attendee.wallet.index') }}"
                    class="block rounded-xl px-3 py-2.5 hover:bg-slate-100 {{ request()->routeIs('attendee.wallet.*') ? $navActive : '' }}">
                    Wallet
                </a>
            @endif

            <a href="{{ route('dashboard') }}"
                class="block rounded-xl px-3 py-2.5 hover:bg-slate-100 {{ request()->routeIs('dashboard') ? $navActive : '' }}">
                Dashboard
            </a>

            <hr class="border-slate-200">

            <a href="{{ route('profile.edit') }}"
                class="block rounded-xl px-3 py-2.5 hover:bg-slate-100">
                Profile Settings
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full rounded-xl px-3 py-2.5 text-left text-red-600 hover:bg-red-50">
                    Sign Out
                </button>
            </form>
        </div>
    </div>
</nav>
