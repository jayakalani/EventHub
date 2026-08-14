<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">
                    {{ __('Host Management') }}
                </p>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                    {{ __('Edit Host') }}
                </h2>
            </div>

            <a href="{{ route('organizer.hosts') }}"
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7" />
                </svg>
                {{ __('Back to Hosts') }}
            </a>
        </div>
    </x-slot>

    <div class="bg-gradient-to-br from-slate-50 via-white to-indigo-50/40 py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <p class="font-semibold text-emerald-800">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <div
                class="overflow-hidden rounded-3xl border border-white/70 bg-white/90 shadow-xl shadow-indigo-100/50 backdrop-blur">
                <div
                    class="border-b border-gray-100 bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500 px-6 py-8 text-white sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 text-2xl font-bold uppercase shadow-inner ring-1 ring-white/25">
                                {{ strtoupper(substr($host->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold tracking-tight">
                                    {{ $host->name }}
                                </h3>
                                <p class="mt-1 text-sm text-indigo-50">
                                    {{ $host->email }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $host->is_active ? 'bg-emerald-400/20 text-white ring-emerald-100/40' : 'bg-rose-400/20 text-white ring-rose-100/40' }}">
                                {{ $host->is_active ? __('Active') : __('Inactive') }}
                            </span>
                            <span
                                class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/25">
                                {{ $host->events()->count() }} {{ __('Events') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-8 p-6 lg:grid-cols-[1fr_320px] lg:p-8">
                    <form method="POST" enctype="multipart/form-data"
                        action="{{ route('organizer.hosts.update', $host) }}" class="space-y-8">
                        @csrf
                        @method('PUT')

                        <div>
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ __('Host Details') }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ __('Update host profile information and cover image.') }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <x-input-label for="name" :value="__('Name')" />
                                    <x-text-input id="name"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="text" name="name" :value="old('name', $host->name)" required autofocus :title-case="true" />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="email" :value="__('Email Address')" />
                                    <x-text-input id="email"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="email" name="email" :value="old('email', $host->email)" required />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm">
                                    <x-input-label for="contact_number" :value="__('Contact Number')" />
                                    <x-text-input id="contact_number"
                                        class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        type="text" name="contact_number" :value="old('contact_number', $host->contact_number)"
                                        inputmode="numeric"
                                        pattern="[0-9]{10}"
                                        maxlength="10"
                                        title="Enter a 10-digit phone number"
                                        oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10)"
                                        required />
                                    <p class="mt-1 text-xs text-gray-500">Must be exactly 10 digits</p>
                                    <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition focus-within:border-indigo-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <x-input-label for="cover" :value="__('Cover Image')" />

                                    @if ($host->cover)
                                        <div class="mt-3 mb-4">
                                            <p class="text-sm font-medium text-gray-700 mb-2">{{ __('Current Cover') }}</p>
                                            <img src="{{ asset('uploads/covers/hosts/' . $host->cover) }}"
                                                alt="{{ __('Current Cover') }}"
                                                class="h-32 w-48 rounded-xl object-cover shadow-sm ring-1 ring-gray-200">
                                        </div>
                                    @endif

                                    <div
                                        class="mt-2 rounded-2xl border-2 border-dashed border-gray-200 bg-white p-6 text-center transition hover:border-indigo-300">
                                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <div class="mt-3">
                                            <label for="cover"
                                                class="cursor-pointer text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                                {{ __('Upload New Cover') }}
                                            </label>
                                            <input id="cover" type="file" name="cover" accept=".jpg,.jpeg,.png"
                                                class="hidden">
                                            <p class="mt-1 text-xs text-gray-500">
                                                {{ __('JPG, JPEG or PNG up to 2MB') }}
                                            </p>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('cover')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:items-center sm:justify-end">
                            <a href="{{ route('organizer.hosts') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button
                                class="justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold shadow-lg shadow-indigo-200 transition hover:bg-indigo-700 focus:ring-indigo-500">
                                {{ __('Update Host') }}
                            </x-primary-button>
                        </div>
                    </form>

                    <aside class="space-y-4">
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                {{ __('Host Summary') }}
                            </h4>
                            <dl class="mt-4 space-y-4 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Host ID') }}</dt>
                                    <dd class="font-semibold text-gray-900">#{{ $host->id }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Contact') }}</dt>
                                    <dd class="font-semibold text-gray-900">{{ $host->contact_number }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Linked Events') }}</dt>
                                    <dd class="font-semibold text-gray-900">{{ $host->events()->count() }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Status') }}</dt>
                                    <dd class="font-semibold text-gray-900">
                                        {{ $host->is_active ? __('Active') : __('Inactive') }}
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
                                        {{ __('Changes to host details will be reflected on all events hosted by this person.') }}
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
