<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

            <div>
                <h2 class="text-3xl font-bold text-slate-900">
                    Create Event Category
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Add a new category to organize events in the system.
                </p>
            </div>

            <a href="{{ route('admin.event-categories.index') }}"
               class="inline-flex items-center px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-medium hover:bg-slate-50 transition">
                ← Back to Categories
            </a>

        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Success Message --}}
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center">
                            ✓
                        </div>
                        <p class="text-emerald-700 font-medium">
                            {{ session('success') }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- Error Message --}}
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
                    <div class="flex items-start gap-3">

                        <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center">
                            !
                        </div>

                        <div>
                            <p class="text-red-800 font-semibold">
                                Please fix the following errors:
                            </p>

                            <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>

                    </div>
                </div>
            @endif

            {{-- Main Form Card --}}
            <form method="POST"
                  action="{{ route('admin.event.category.store') }}"
                  enctype="multipart/form-data">

                @csrf

                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">

                    {{-- Header --}}
                    <div class="px-8 py-6 border-b border-slate-100">
                        <h3 class="text-xl font-semibold text-slate-900">
                            Category Details
                        </h3>
                        <p class="text-sm text-slate-500 mt-1">
                            Fill in the required information below
                        </p>
                    </div>

                    <div class="p-8 space-y-8">

                        {{-- Category Name --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Category Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                placeholder="e.g. Music Concert, Workshop, Sports Event"
                                class="w-full rounded-2xl border-slate-300 shadow-sm
                                focus:border-indigo-500 focus:ring-indigo-500">

                            <p class="mt-2 text-xs text-slate-500">
                                Choose a clear and meaningful category name.
                            </p>
                        </div>

                        {{-- Cover Upload --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-3">
                                Cover Image
                            </label>

                            <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-8 text-center hover:border-indigo-400 transition">

                                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>

                                <div class="mt-4">
                                    <label for="cover"
                                           class="cursor-pointer text-indigo-600 font-semibold hover:text-indigo-700">
                                        Click to upload cover image
                                    </label>

                                    <input
                                        id="cover"
                                        type="file"
                                        name="cover"
                                        accept=".jpg,.jpeg,.png"
                                        required
                                        class="hidden">

                                    <p class="text-xs text-slate-500 mt-2">
                                        JPG, JPEG or PNG (Max 2MB)
                                    </p>
                                </div>

                            </div>
                        </div>

                    </div>

                    {{-- Footer Actions --}}
                    <div class="border-t border-slate-100 bg-slate-50 px-8 py-5">

                        <div class="flex flex-col sm:flex-row justify-end gap-3">

                            <a href="{{ route('admin.event-categories.index') }}"
                               class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 font-medium hover:bg-slate-100 transition text-center">
                                Cancel
                            </a>

                            <button type="submit"
                                    class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold shadow hover:bg-indigo-700 transition">
                                Create Category
                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>
    </div>

</x-app-layout>