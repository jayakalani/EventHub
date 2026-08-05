<x-app-layout>
    @php
        $fieldClass = 'w-full rounded-lg border border-white/70 bg-white/60 px-3 py-2.5 text-sm text-slate-700 shadow-sm backdrop-blur-md placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500';
        $labelClass = 'mb-1.5 block text-xs font-semibold text-slate-600';
    @endphp

    <div class="admin-create-event-category relative isolate overflow-hidden py-5 sm:py-6"
        x-data="{
            coverName: '',
            coverPreview: null,
            onCoverChange(event) {
                const file = event.target.files?.[0];
                if (!file) {
                    this.coverName = '';
                    this.coverPreview = null;
                    return;
                }
                this.coverName = file.name;
                this.coverPreview = URL.createObjectURL(file);
            }
        }">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-violet-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- Hero --}}
            <section class="glass-panel overflow-hidden !rounded-2xl">
                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-indigo-200/35 blur-2xl"></div>
                    <div class="pointer-events-none absolute -bottom-12 left-1/4 h-28 w-28 rounded-full bg-cyan-200/25 blur-2xl"></div>

                    <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600/90 text-white shadow-sm ring-2 ring-white/70 sm:h-10 sm:w-10">
                                    <i class="bi bi-tag-fill text-sm sm:text-base"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">Catalog management</p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        Create Event Category
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 text-sm text-slate-500">
                                Add a category to organize events in the catalog ·
                                {{ now()->format('l, M j, Y') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <a href="{{ route('admin.event-categories.index') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-arrow-left"></i>
                                Back to Categories
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Context tips --}}
            <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="glass-card kpi-lift group border-t-4 border-t-indigo-500 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Naming</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">Clear labels</p>
                            <p class="mt-1 text-xs text-slate-500">Use specific, searchable names</p>
                        </div>
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100/70 text-indigo-600 transition-transform duration-300 group-hover:scale-110">
                            <i class="bi bi-type"></i>
                        </div>
                    </div>
                </div>

                <div class="glass-card kpi-lift group border-t-4 border-t-cyan-500 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Visual</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">Cover image</p>
                            <p class="mt-1 text-xs text-slate-500">JPG or PNG, max 2MB</p>
                        </div>
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-100/70 text-cyan-600 transition-transform duration-300 group-hover:scale-110">
                            <i class="bi bi-image"></i>
                        </div>
                    </div>
                </div>

                <div class="glass-card kpi-lift group border-t-4 border-t-emerald-500 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">Active by default</p>
                            <p class="mt-1 text-xs text-slate-500">Ready for event assignment</p>
                        </div>
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100/70 text-emerald-600 transition-transform duration-300 group-hover:scale-110">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                </div>
            </section>

            @if (session('success'))
                <div
                    class="glass-panel !rounded-2xl border border-emerald-200/70 bg-emerald-50/70 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm backdrop-blur">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-check-circle-fill"></i>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div
                    class="glass-panel !rounded-2xl border border-rose-200/70 bg-rose-50/70 px-4 py-4 text-sm text-rose-700 shadow-sm backdrop-blur">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-rose-800">Please fix the following errors</p>
                            <ul class="mt-2 list-inside list-disc space-y-1 text-rose-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('admin.event.category.store') }}" enctype="multipart/form-data"
                class="space-y-5">
                @csrf

                <section class="glass-card overflow-hidden !p-0 !rounded-2xl">
                    <div class="border-b border-white/60 px-4 py-4 sm:px-6">
                        <h2 class="text-sm font-semibold text-slate-900">Category details</h2>
                        <p class="text-xs text-slate-500">Fill in the required information below.</p>
                    </div>

                    <div class="space-y-6 p-4 sm:p-6">
                        {{-- Name --}}
                        <div>
                            <div class="mb-3 flex items-center gap-2">
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100/80 text-indigo-600">
                                    <i class="bi bi-tag text-sm"></i>
                                </span>
                                <h3 class="text-sm font-semibold text-slate-800">Category identity</h3>
                            </div>

                            <div
                                class="btn-smooth rounded-xl border border-white/60 bg-white/40 p-3 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm focus-within:border-indigo-200 focus-within:bg-white/80">
                                <label for="name" class="{{ $labelClass }}">Category name</label>
                                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                                    data-title-case placeholder="e.g. Music Concert, Workshop, Sports Event"
                                    class="{{ $fieldClass }}">
                                <p class="mt-1.5 text-[11px] font-medium text-slate-500">
                                    Choose a clear and meaningful category name.
                                </p>
                            </div>
                        </div>

                        {{-- Cover --}}
                        <div>
                            <div class="mb-3 flex items-center gap-2">
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-cyan-100/80 text-cyan-600">
                                    <i class="bi bi-image text-sm"></i>
                                </span>
                                <h3 class="text-sm font-semibold text-slate-800">Cover image</h3>
                            </div>

                            <div
                                class="btn-smooth rounded-xl border border-dashed border-indigo-200/80 bg-white/40 p-4 backdrop-blur-sm transition hover:-translate-y-0.5 hover:border-indigo-400 hover:bg-white/70 hover:shadow-sm sm:p-6">
                                <input id="cover" type="file" name="cover" accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                    required class="sr-only" @change="onCoverChange($event)">

                                <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                                    <div
                                        class="flex h-28 w-full max-w-[10rem] shrink-0 items-center justify-center overflow-hidden rounded-xl border border-white/70 bg-slate-100/80 shadow-sm sm:h-32">
                                        <template x-if="coverPreview">
                                            <img :src="coverPreview" alt="Cover preview"
                                                class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!coverPreview">
                                            <div class="flex flex-col items-center gap-1 text-slate-400">
                                                <i class="bi bi-cloud-arrow-up text-2xl"></i>
                                                <span class="text-[10px] font-semibold uppercase tracking-wide">Preview</span>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="min-w-0 flex-1 text-center sm:text-left">
                                        <label for="cover"
                                            class="btn-smooth inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 sm:text-sm">
                                            <i class="bi bi-upload"></i>
                                            <span x-text="coverName ? 'Change image' : 'Upload cover image'"></span>
                                        </label>

                                        <p class="mt-2 text-xs font-medium text-slate-500">
                                            JPG, JPEG or PNG · Max 2MB
                                        </p>

                                        <p class="mt-1 truncate text-xs font-semibold text-indigo-700"
                                            x-show="coverName" x-text="coverName" x-cloak></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex flex-col-reverse gap-2 border-t border-white/60 bg-white/30 px-4 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                        <a href="{{ route('admin.event-categories.index') }}"
                            class="btn-smooth inline-flex items-center justify-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80">
                            Cancel
                        </a>
                        <button type="submit"
                            class="btn-smooth inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow-md">
                            <i class="bi bi-check2-circle"></i>
                            Create Category
                        </button>
                    </div>
                </section>
            </form>
        </div>
    </div>
</x-app-layout>
