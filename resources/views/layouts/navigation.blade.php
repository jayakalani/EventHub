@php
    $userRole = Auth::user()?->userRole?->name_en;
    $isAttendee = $userRole === \App\Models\UserRole::ATTENDEE;

    $user = Auth::user();
@endphp

<nav x-data="{ open: false }"
    class="sticky top-0 z-50 bg-white/90 backdrop-blur-lg border-b border-slate-200 shadow-sm">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex justify-between items-center h-16">

            {{-- Left Section --}}
            <div class="flex items-center gap-10">

                {{-- Logo --}}
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 shrink-0">

                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md">
                        <x-application-logo class="h-6 w-6 fill-current" />
                    </div>

                    <div class="hidden md:block">
                        <p class="font-bold text-slate-900 text-lg">
                            EventHub
                        </p>
                        <p class="text-xs text-slate-500 -mt-1">
                            Event Management Platform
                        </p>
                    </div>

                </a>

                {{-- Desktop Navigation --}}
                <div class="hidden lg:flex items-center gap-2">

                    @if($isAttendee)

                        <a href="{{ route('attendee.dashboard') }}"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition
                            {{ request()->routeIs('attendee.dashboard','attendee.events.*')
                                ? 'bg-indigo-50 text-indigo-600'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            Events
                        </a>

                        <a href="{{ route('attendee.hosts.index') }}"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition
                            {{ request()->routeIs('attendee.hosts.*')
                                ? 'bg-indigo-50 text-indigo-600'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            Hosts
                        </a>

                        <a href="{{ route('attendee.bookings.index') }}"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition relative
                            {{ request()->routeIs('attendee.bookings.*')
                                ? 'bg-indigo-50 text-indigo-600'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            Tickets
                        </a>

                        <a href="{{ route('attendee.wallet.index') }}"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition
                            {{ request()->routeIs('attendee.wallet.*')
                                ? 'bg-indigo-50 text-indigo-600'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            Wallet
                        </a>

                        <a href="{{ route('dashboard') }}"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition
                            {{ request()->routeIs('dashboard')
                                ? 'bg-indigo-50 text-indigo-600'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            Dashboard
                        </a>

                    @else

                        <a href="{{ route('dashboard') }}"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition
                            {{ request()->routeIs('dashboard')
                                ? 'bg-indigo-50 text-indigo-600'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            Dashboard
                        </a>

                    @endif

                </div>

            </div>

            {{-- Right Section --}}
            <div class="flex items-center gap-3">

                {{-- Cart --}}
                @if($isAttendee)

                    <a href="{{ route('attendee.cart.index') }}"
                        class="relative hidden sm:flex items-center justify-center h-10 w-10 rounded-xl bg-slate-100 hover:bg-slate-200 transition">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-slate-700"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">

                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8L5.4 5M7 13l-1.3 6h11.6L17 13M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/>

                        </svg>

                        {{-- Cart Badge --}}
                        @if(($cartItemCount ?? 0) > 0)
                            <span class="absolute -top-1 -right-1 h-5 min-w-5 rounded-full bg-red-500 px-1 text-white text-xs flex items-center justify-center font-bold">
                                {{ $cartItemCount }}
                            </span>
                        @endif

                    </a>

                @endif

                {{-- Profile Dropdown --}}
                <div class="hidden sm:block">

                    <x-dropdown align="right" width="56">

                        <x-slot name="trigger">

                            <button
                                class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 hover:bg-slate-50 transition">

                                <div
                                    class="h-9 w-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">

                                    {{ strtoupper(substr($user?->name ?? 'G',0,1)) }}

                                </div>
                                
                                <svg class="h-4 w-4 text-slate-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">

                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"/>

                                </svg>

                            </button>

                        </x-slot>

                        <x-slot name="content">

                            <div class="px-4 py-3 border-b border-slate-100">

                                <p class="font-semibold text-slate-900">
                                    {{ $user?->name }}
                                </p>

                                <p class="text-sm text-slate-500">
                                    {{ $user?->email }}
                                </p>

                            </div>

                            <x-dropdown-link :href="route('profile.edit')">
                                Profile Settings
                            </x-dropdown-link>

                            <form method="POST"
                                action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    Sign Out
                                </x-dropdown-link>
                            </form>

                        </x-slot>

                    </x-dropdown>

                </div>

                {{-- Mobile Menu Button --}}
                <button
                    @click="open = !open"
                    class="lg:hidden flex items-center justify-center h-10 w-10 rounded-xl bg-slate-100 hover:bg-slate-200">

                    <svg class="h-6 w-6 text-slate-700"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">

                        <path
                            x-show="!open"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"/>

                        <path
                            x-show="open"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"/>

                    </svg>

                </button>

            </div>

        </div>

    </div>

    {{-- Mobile Menu --}}
    <div x-show="open"
        x-transition
        class="lg:hidden border-t border-slate-200 bg-white">

        <div class="px-4 py-4 space-y-2">

            @if($isAttendee)

                <a href="{{ route('attendee.dashboard') }}"
                    class="block px-4 py-3 rounded-xl hover:bg-slate-100">
                    Events
                </a>

                <a href="{{ route('attendee.hosts.index') }}"
                    class="block px-4 py-3 rounded-xl hover:bg-slate-100">
                    Hosts
                </a>

                <a href="{{ route('attendee.bookings.index') }}"
                    class="flex items-center justify-between px-4 py-3 rounded-xl hover:bg-slate-100">
                    <span>Tickets</span>
                    @if(($reservedTicketCount ?? 0) > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-500 px-1.5 text-xs font-bold text-white">
                            {{ $reservedTicketCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('attendee.cart.index') }}"
                    class="flex items-center justify-between px-4 py-3 rounded-xl hover:bg-slate-100">
                    <span>Cart</span>
                    @if(($cartItemCount ?? 0) > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white">
                            {{ $cartItemCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('attendee.wallet.index') }}"
                    class="block px-4 py-3 rounded-xl hover:bg-slate-100">
                    Wallet
                </a>

            @endif

            <a href="{{ route('dashboard') }}"
                class="block px-4 py-3 rounded-xl hover:bg-slate-100">
                Dashboard
            </a>

            <hr>

            <a href="{{ route('profile.edit') }}"
                class="block px-4 py-3 rounded-xl hover:bg-slate-100">
                Profile Settings
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit"
                    class="w-full text-left px-4 py-3 rounded-xl hover:bg-red-50 text-red-600">
                    Sign Out
                </button>
            </form>

        </div>

    </div>

</nav>