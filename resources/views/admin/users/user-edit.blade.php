<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">
                    {{ __('User Management') }}
                </p>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                    {{ __('Edit User') }}
                </h2>
            </div>

            <a href="{{ route('admin.users') }}"
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7" />
                </svg>
                {{ __('Back to Users') }}
            </a>
        </div>
    </x-slot>

    <div class="bg-gradient-to-br from-slate-50 via-white to-indigo-50/40 py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div
                class="overflow-hidden rounded-3xl border border-white/70 bg-white/90 shadow-xl shadow-indigo-100/50 backdrop-blur">
                <div
                    class="border-b border-gray-100 bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500 px-6 py-8 text-white sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 text-2xl font-bold uppercase shadow-inner ring-1 ring-white/25">
                                {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold tracking-tight">
                                    {{ $user->first_name }} {{ $user->last_name }}
                                </h3>
                                <p class="mt-1 text-sm text-indigo-50">
                                    {{ $user->email }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/25">
                                {{ $user->userRole?->name_en ?? __('No Role') }}
                            </span>
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $user->is_active ? 'bg-emerald-400/20 text-white ring-emerald-100/40' : 'bg-rose-400/20 text-white ring-rose-100/40' }}">
                                {{ $user->is_active ? __('Active') : __('Inactive') }}
                            </span>
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $user->is_locked ? 'bg-amber-400/20 text-white ring-amber-100/40' : 'bg-white/15 text-white ring-white/25' }}">
                                {{ $user->is_locked ? __('Locked') : __('Unlocked') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-8 p-6 lg:grid-cols-[1fr_320px] lg:p-8">
                    <form method="POST" action="{{ route('admin.user.update', $user->id) }}" class="space-y-8">
                        @csrf
                        @method('PUT')

                        <div>
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ __('Account Details') }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ __('Update the user profile information and assigned access role.') }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- First Name -->
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="first_name" :value="__('First Name')" />
                                    <x-text-input id="first_name"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="text" name="first_name" :value="old('first_name', $user->first_name)" required :title-case="true" />
                                    <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                                </div>

                                <!-- Last Name -->
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="last_name" :value="__('Last Name')" />
                                    <x-text-input id="last_name"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="text" name="last_name" :value="old('last_name', $user->last_name)" required :title-case="true" />
                                    <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                                </div>

                                <!-- Email -->
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="email" :value="__('Email')" />
                                    <x-text-input id="email"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="email" name="email" :value="old('email', $user->email)" required />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>

                                <!-- Contact Number -->
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="contact_number" :value="__('Contact Number')" />
                                    <x-text-input id="contact_number"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="text" name="contact_number" :value="old('contact_number', $user->contact_number)" required />
                                    <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
                                </div>

                                <!-- Role -->
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <x-input-label for="role_id" :value="__('Role')" />
                                    <select id="role_id" name="role_id"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}"
                                                {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                                {{ $role->name_en }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:items-center sm:justify-end">
                            <a href="{{ route('admin.users') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button
                                class="justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold shadow-lg shadow-indigo-200 transition hover:bg-indigo-700 focus:ring-indigo-500">
                                {{ __('Update') }}
                            </x-primary-button>
                        </div>
                    </form>

                    <aside class="space-y-4">
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                {{ __('User Summary') }}
                            </h4>
                            <dl class="mt-4 space-y-4 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('User ID') }}</dt>
                                    <dd class="font-semibold text-gray-900">#{{ $user->id }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Role') }}</dt>
                                    <dd class="font-semibold text-gray-900">
                                        {{ $user->userRole?->name_en ?? __('Not assigned') }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Joined') }}</dt>
                                    <dd class="font-semibold text-gray-900">{{ $user->created_at?->format('M d, Y') }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5">
                            <div class="flex gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-indigo-950">{{ __('Review before updating') }}</h4>
                                    <p class="mt-1 text-sm leading-6 text-indigo-800">
                                        {{ __('Changing the role can immediately affect this user\'s access permissions.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
