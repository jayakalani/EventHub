<x-app-layout>
    @php
        $statusLabels = [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];

        $kpis = [
            [
                'label' => 'Matched',
                'value' => $stats['matched'],
                'sub' => $hasActiveFilters ? 'After active filters' : 'All categories',
                'icon' => 'bi-tags',
                'accent' => 'indigo',
            ],
            [
                'label' => 'Active',
                'value' => $stats['active'],
                'sub' => 'Available for events',
                'icon' => 'bi-check-circle',
                'accent' => 'emerald',
            ],
            [
                'label' => 'Inactive',
                'value' => $stats['inactive'],
                'sub' => 'Hidden from selection',
                'icon' => 'bi-pause-circle',
                'accent' => 'rose',
            ],
            [
                'label' => 'All Time',
                'value' => $stats['total'],
                'sub' => 'Total stored categories',
                'icon' => 'bi-collection',
                'accent' => 'cyan',
            ],
        ];

        $activeFilterChips = array_filter([
            'search' => request('search') ? 'Search: '.request('search') : null,
            'status' => request('status') ? 'Status: '.($statusLabels[request('status')] ?? request('status')) : null,
            'from_date' => request('from_date') ? 'From: '.request('from_date') : null,
            'to_date' => request('to_date') ? 'To: '.request('to_date') : null,
        ]);
    @endphp

    <div class="admin-event-categories relative isolate overflow-hidden py-5 sm:py-6">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-violet-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

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
                                    <i class="bi bi-tags text-sm sm:text-base"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">Catalog management</p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        Event Categories
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 text-sm text-slate-500">
                                Manage categories and their availability for events ·
                                {{ now()->format('l, M j, Y') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <a href="{{ route('admin.event.category.create') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg bg-indigo-600/95 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow-md sm:text-sm">
                                <i class="bi bi-plus-lg"></i>
                                New Category
                            </a>
                            <a href="{{ route('admin.event-categories.export.csv') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-filetype-csv"></i>
                                Export CSV
                            </a>
                            <a href="{{ route('admin.event-categories.export.pdf') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-file-earmark-pdf"></i>
                                Export PDF
                            </a>
                            <a href="{{ route('dashboard') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-arrow-left"></i>
                                Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            {{-- KPI snapshot --}}
            <section class="space-y-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Category snapshot</h2>
                    <p class="text-xs text-slate-500">Availability across the event catalog.</p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($kpis as $kpi)
                        @php
                            $accent = match ($kpi['accent']) {
                                'indigo' => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/70', 'iconText' => 'text-indigo-600', 'value' => 'text-indigo-700'],
                                'emerald' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/70', 'iconText' => 'text-emerald-600', 'value' => 'text-emerald-700'],
                                'rose' => ['top' => 'border-t-rose-500', 'iconBg' => 'bg-rose-100/70', 'iconText' => 'text-rose-600', 'value' => 'text-rose-700'],
                                default => ['top' => 'border-t-cyan-500', 'iconBg' => 'bg-cyan-100/70', 'iconText' => 'text-cyan-600', 'value' => 'text-cyan-700'],
                            };
                        @endphp
                        <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        {{ $kpi['label'] }}
                                    </p>
                                    <p class="mt-1 truncate text-2xl font-bold tracking-tight {{ $accent['value'] }}">
                                        {{ number_format($kpi['value']) }}
                                    </p>
                                    <p class="mt-1 text-xs font-medium text-slate-500">{{ $kpi['sub'] }}</p>
                                </div>
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['iconBg'] }} backdrop-blur-sm transition-transform duration-300 group-hover:scale-110">
                                    <i class="bi {{ $kpi['icon'] }} text-lg {{ $accent['iconText'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Filters --}}
            <section class="glass-panel !rounded-2xl p-4 sm:p-5">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Filter categories</h2>
                        <p class="text-xs text-slate-500">Search by name, status, or creation date.</p>
                    </div>

                    @if ($hasActiveFilters)
                        <a href="{{ route('admin.event-categories.index') }}"
                            class="btn-smooth inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            <i class="bi bi-x-circle"></i>
                            Clear all filters
                        </a>
                    @endif
                </div>

                <form method="GET" action="{{ route('admin.event-categories.index') }}"
                    class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12">
                    <div class="xl:col-span-4">
                        <label for="category_search" class="mb-1.5 block text-xs font-semibold text-slate-600">Search</label>
                        <div class="relative">
                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input id="category_search" type="text" name="search" value="{{ request('search') }}"
                                placeholder="Search category..."
                                class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-9 pr-3 text-sm text-slate-700 shadow-sm backdrop-blur-md placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="xl:col-span-2">
                        <label for="category_status" class="mb-1.5 block text-xs font-semibold text-slate-600">Status</label>
                        <select id="category_status" name="status"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All status</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label for="category_from" class="mb-1.5 block text-xs font-semibold text-slate-600">From</label>
                        <input id="category_from" type="date" name="from_date" value="{{ request('from_date') }}"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 px-3 text-sm text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="xl:col-span-2">
                        <label for="category_to" class="mb-1.5 block text-xs font-semibold text-slate-600">To</label>
                        <input id="category_to" type="date" name="to_date" value="{{ request('to_date') }}"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 px-3 text-sm text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="flex items-end gap-2 xl:col-span-2">
                        <button type="submit"
                            class="btn-smooth inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                            <i class="bi bi-funnel"></i>
                            Apply
                        </button>
                    </div>
                </form>

                @if ($hasActiveFilters)
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-white/60 pt-4">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active</span>
                        @foreach ($activeFilterChips as $chip)
                            <span
                                class="inline-flex items-center rounded-full border border-indigo-100 bg-indigo-50/80 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                {{ $chip }}
                            </span>
                        @endforeach
                    </div>
                @endif
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

            {{-- Directory table --}}
            <section class="glass-card overflow-hidden !p-0 !rounded-2xl">
                <div class="flex flex-col gap-2 border-b border-white/60 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Category directory</h2>
                        <p class="text-xs text-slate-500">
                            Showing
                            <span class="font-semibold text-slate-700">{{ $event_categories->firstItem() ?? 0 }}</span>–
                            <span class="font-semibold text-slate-700">{{ $event_categories->lastItem() ?? 0 }}</span>
                            of
                            <span class="font-semibold text-slate-700">{{ number_format($event_categories->total()) }}</span>
                            categories
                        </p>
                    </div>
                    <p class="text-xs font-medium text-slate-500">
                        Page {{ $event_categories->currentPage() }} of {{ max(1, $event_categories->lastPage()) }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-white/40 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-4 py-3 sm:px-6">Category</th>
                                <th class="px-4 py-3 sm:px-6">Created by</th>
                                <th class="px-4 py-3 sm:px-6">Status</th>
                                <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100/80">
                            @forelse ($event_categories as $category)
                                @php
                                    $creatorName = $category->creator
                                        ? trim(($category->creator->first_name ?? '').' '.($category->creator->last_name ?? ''))
                                        : 'System';
                                    $creatorInitials = $category->creator
                                        ? strtoupper(substr($category->creator->first_name ?? 'U', 0, 1).substr($category->creator->last_name ?? '', 0, 1))
                                        : 'SY';
                                @endphp

                                <tr class="btn-smooth align-middle hover:bg-white/45">
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500/90 to-cyan-500/80 text-white shadow-sm ring-2 ring-white/80">
                                                <i class="bi bi-tag-fill text-sm"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <p class="truncate text-sm font-semibold text-slate-900">
                                                        {{ $category->name }}
                                                    </p>
                                                    <span class="font-mono text-[11px] text-slate-400">#{{ $category->id }}</span>
                                                </div>
                                                <p class="mt-0.5 text-xs text-slate-500">
                                                    Added {{ $category->created_at?->format('d M Y') ?? '—' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-600 ring-1 ring-white">
                                                {{ $creatorInitials }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-800">{{ $creatorName }}</p>
                                                <p class="truncate text-xs text-slate-500">
                                                    {{ $category->creator?->email ?? 'Automated entry' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 sm:px-6">
                                        <form action="{{ route('admin.event.category.toggleActive', $category->id) }}"
                                            method="POST">
                                            @csrf
                                            <button type="submit"
                                                onclick="return confirm('Are you sure you want to set {{ $category->name }} as {{ $category->is_active ? 'inactive' : 'active' }}?')"
                                                class="btn-smooth inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset transition hover:-translate-y-0.5 hover:shadow-sm
                                                {{ $category->is_active
                                                    ? 'bg-emerald-100 text-emerald-700 ring-emerald-200/70 hover:bg-emerald-200/80'
                                                    : 'bg-rose-100 text-rose-700 ring-rose-200/70 hover:bg-rose-200/80' }}">
                                                <i class="bi {{ $category->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                                                {{ $category->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    </td>

                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.event.category.edit', $category->id) }}"
                                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-indigo-100 bg-indigo-50/80 px-3 py-2 text-xs font-semibold text-indigo-700 backdrop-blur hover:-translate-y-0.5 hover:bg-indigo-100 hover:shadow-sm">
                                                <i class="bi bi-pencil-square"></i>
                                                Edit
                                            </a>

                                            <form action="{{ route('admin.event.category.destroy', $category->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    onclick="return confirm('Delete this category?')"
                                                    class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-rose-100 bg-rose-50/80 px-3 py-2 text-xs font-semibold text-rose-700 backdrop-blur hover:-translate-y-0.5 hover:bg-rose-100 hover:shadow-sm">
                                                    <i class="bi bi-trash3"></i>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-16 text-center">
                                        <div
                                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <i class="bi bi-tags text-xl"></i>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-slate-700">No event categories found</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            @if ($hasActiveFilters)
                                                Try adjusting or clearing your filters.
                                            @else
                                                Create a category to organize events.
                                            @endif
                                        </p>
                                        @if ($hasActiveFilters)
                                            <a href="{{ route('admin.event-categories.index') }}"
                                                class="btn-smooth mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Clear filters
                                            </a>
                                        @else
                                            <a href="{{ route('admin.event.category.create') }}"
                                                class="btn-smooth mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                <i class="bi bi-plus-lg"></i>
                                                New Category
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($event_categories->hasPages())
                    <div class="border-t border-white/60 bg-white/30 px-4 py-4 sm:px-6">
                        {{ $event_categories->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
