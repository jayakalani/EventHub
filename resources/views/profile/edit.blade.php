<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">
                    {{ __('Account Settings') }}
                </p>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                    {{ __('Profile') }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="bg-gradient-to-br from-slate-50 via-white to-indigo-50/40 py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div
                class="mb-8 overflow-hidden rounded-3xl border border-white/70 bg-white/90 shadow-xl shadow-indigo-100/50 backdrop-blur">
                <div
                    class="border-b border-gray-100 bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500 px-6 py-8 text-white sm:px-8">
                    <div class="flex items-center gap-4">
                        @if ($user->profile_photo)
                            <img src="{{ asset('uploads/users-profile-photos/' . $user->profile_photo) }}"
                                alt="{{ $user->full_name }}"
                                class="h-16 w-16 rounded-2xl object-cover shadow-inner ring-1 ring-white/25">
                        @else
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 text-2xl font-bold uppercase shadow-inner ring-1 ring-white/25">
                                {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <h3 class="text-2xl font-bold tracking-tight">
                                {{ $user->full_name }}
                            </h3>
                            <p class="mt-1 text-sm text-indigo-50">
                                {{ $user->email }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div
                    class="overflow-hidden rounded-3xl border border-white/70 bg-white/90 p-6 shadow-lg shadow-indigo-100/30 backdrop-blur sm:p-8">
                    <div class="max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-3xl border border-white/70 bg-white/90 p-6 shadow-lg shadow-indigo-100/30 backdrop-blur sm:p-8">
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-3xl border border-white/70 bg-white/90 p-6 shadow-lg shadow-indigo-100/30 backdrop-blur sm:p-8">
                    <div class="max-w-xl">
                        @include('profile.partials.two-factor-authentication-form')
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-3xl border border-white/70 bg-white/90 p-6 shadow-lg shadow-indigo-100/30 backdrop-blur sm:p-8">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
