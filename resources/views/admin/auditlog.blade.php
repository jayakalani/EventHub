<x-app-layout>
    @php
        $kpis = [
            [
                'label' => 'Matched Results',
                'value' => $stats['matched'],
                'sub' => $hasActiveFilters ? 'After active filters' : 'All audit entries',
                'icon' => 'bi-funnel',
                'accent' => 'indigo',
            ],
            [
                'label' => 'Today',
                'value' => $stats['today'],
                'sub' => 'Logged in the last 24h window',
                'icon' => 'bi-calendar-day',
                'accent' => 'emerald',
            ],
            [
                'label' => 'This Week',
                'value' => $stats['thisWeek'],
                'sub' => 'Since start of week',
                'icon' => 'bi-calendar-week',
                'accent' => 'cyan',
            ],
            [
                'label' => 'All Time',
                'value' => $stats['total'],
                'sub' => 'Total stored audit records',
                'icon' => 'bi-journal-text',
                'accent' => 'slate',
            ],
        ];

        $activeFilterChips = array_filter([
            'search' => request('search') ? 'Search: '.request('search') : null,
            'action' => request('action') ? 'Action: '.request('action') : null,
            'model_type' => request('model_type') ? 'Model: '.class_basename(request('model_type')) : null,
            'from_date' => request('from_date') ? 'From: '.request('from_date') : null,
            'to_date' => request('to_date') ? 'To: '.request('to_date') : null,
        ]);
    @endphp

    <div class="admin-audit-logs relative isolate overflow-hidden py-5 sm:py-6">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-amber-300/15 blur-3xl"></div>
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
                                    <i class="bi bi-shield-check text-sm sm:text-base"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">Security & compliance</p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        Audit Logs
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 text-sm text-slate-500">
                                Track system activity, model changes, and security-related events ·
                                {{ now()->format('l, M j, Y') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <a href="{{ route('admin.audit-logs.export.csv', request()->query()) }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg bg-indigo-600/95 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow-md sm:text-sm">
                                <i class="bi bi-filetype-csv"></i>
                                Export CSV
                            </a>
                            <a href="{{ route('admin.audit-logs.export.pdf', request()->query()) }}"
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
                    <h2 class="text-sm font-semibold text-slate-900">Activity snapshot</h2>
                    <p class="text-xs text-slate-500">Quick view of audit volume across the selected scope.</p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($kpis as $kpi)
                        @php
                            $accent = match ($kpi['accent']) {
                                'indigo' => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/70', 'iconText' => 'text-indigo-600', 'value' => 'text-indigo-700'],
                                'emerald' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/70', 'iconText' => 'text-emerald-600', 'value' => 'text-emerald-700'],
                                'cyan' => ['top' => 'border-t-cyan-500', 'iconBg' => 'bg-cyan-100/70', 'iconText' => 'text-cyan-600', 'value' => 'text-cyan-700'],
                                default => ['top' => 'border-t-slate-400', 'iconBg' => 'bg-slate-100/70', 'iconText' => 'text-slate-600', 'value' => 'text-slate-800'],
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
                        <h2 class="text-sm font-semibold text-slate-900">Filter activity</h2>
                        <p class="text-xs text-slate-500">Narrow logs by user, action, model, or date range.</p>
                    </div>

                    @if ($hasActiveFilters)
                        <a href="{{ route('admin.audit-logs') }}"
                            class="btn-smooth inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            <i class="bi bi-x-circle"></i>
                            Clear all filters
                        </a>
                    @endif
                </div>

                <form method="GET" action="{{ route('admin.audit-logs') }}"
                    class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12">
                    <div class="xl:col-span-3">
                        <label for="audit_search" class="mb-1.5 block text-xs font-semibold text-slate-600">Search</label>
                        <div class="relative">
                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input id="audit_search" type="text" name="search" value="{{ request('search') }}"
                                placeholder="User, action, IP, model ID..."
                                class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-9 pr-3 text-sm text-slate-700 shadow-sm backdrop-blur-md placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="xl:col-span-2">
                        <label for="audit_action" class="mb-1.5 block text-xs font-semibold text-slate-600">Action</label>
                        <select id="audit_action" name="action"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All actions</option>
                            @foreach ($actions as $action)
                                <option value="{{ $action }}" @selected(request('action') === $action)>
                                    {{ $action }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label for="audit_model" class="mb-1.5 block text-xs font-semibold text-slate-600">Model</label>
                        <select id="audit_model" name="model_type"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All models</option>
                            @foreach ($modelTypes as $modelType)
                                <option value="{{ $modelType }}" @selected(request('model_type') === $modelType)>
                                    {{ class_basename($modelType) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label for="audit_from" class="mb-1.5 block text-xs font-semibold text-slate-600">From</label>
                        <input id="audit_from" type="date" name="from_date" value="{{ request('from_date') }}"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 px-3 text-sm text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="xl:col-span-2">
                        <label for="audit_to" class="mb-1.5 block text-xs font-semibold text-slate-600">To</label>
                        <input id="audit_to" type="date" name="to_date" value="{{ request('to_date') }}"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 px-3 text-sm text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="flex items-end gap-2 xl:col-span-1">
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

            {{-- Activity table --}}
            <section class="glass-card overflow-hidden !p-0 !rounded-2xl">
                <div class="flex flex-col gap-2 border-b border-white/60 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Activity directory</h2>
                        <p class="text-xs text-slate-500">
                            Showing
                            <span class="font-semibold text-slate-700">{{ $logs->firstItem() ?? 0 }}</span>–
                            <span class="font-semibold text-slate-700">{{ $logs->lastItem() ?? 0 }}</span>
                            of
                            <span class="font-semibold text-slate-700">{{ number_format($logs->total()) }}</span>
                            entries
                        </p>
                    </div>
                    <p class="text-xs font-medium text-slate-500">
                        Page {{ $logs->currentPage() }} of {{ max(1, $logs->lastPage()) }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-white/40 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-4 py-3 sm:px-6">When</th>
                                <th class="px-4 py-3 sm:px-6">Actor</th>
                                <th class="px-4 py-3 sm:px-6">Action</th>
                                <th class="px-4 py-3 sm:px-6">Resource</th>
                                <th class="px-4 py-3 sm:px-6">Changes</th>
                                <th class="px-4 py-3 sm:px-6">IP</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100/80">
                            @forelse ($logs as $log)
                                @php
                                    $actionKey = strtolower($log->action);
                                    $badgeColor = match (true) {
                                        str_contains($actionKey, 'creat') => 'bg-emerald-100 text-emerald-700 ring-emerald-200/70',
                                        str_contains($actionKey, 'updat') || str_contains($actionKey, 'reschedul') => 'bg-blue-100 text-blue-700 ring-blue-200/70',
                                        str_contains($actionKey, 'delet') => 'bg-rose-100 text-rose-700 ring-rose-200/70',
                                        str_contains($actionKey, 'postpon') || str_contains($actionKey, 'refund') => 'bg-amber-100 text-amber-700 ring-amber-200/70',
                                        default => 'bg-slate-100 text-slate-700 ring-slate-200/70',
                                    };

                                    $userName = $log->user?->full_name ?? 'System';
                                    $initials = $log->user
                                        ? strtoupper(substr($log->user->first_name ?? 'U', 0, 1).substr($log->user->last_name ?? '', 0, 1))
                                        : 'SY';

                                    $oldValues = is_array($log->old_values)
                                        ? json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                        : $log->old_values;
                                    $newValues = is_array($log->new_values)
                                        ? json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                        : $log->new_values;
                                    $hasChanges = filled($oldValues) || filled($newValues);
                                @endphp

                                <tr class="btn-smooth align-top hover:bg-white/45">
                                    <td class="whitespace-nowrap px-4 py-4 sm:px-6">
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ $log->created_at->format('d M Y') }}
                                        </div>
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $log->created_at->format('h:i A') }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-600 ring-1 ring-white">
                                                {{ $initials }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-900">{{ $userName }}</p>
                                                <p class="truncate text-xs text-slate-500">
                                                    {{ $log->user?->email ?? 'Automated system event' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 sm:px-6">
                                        <span
                                            class="inline-flex max-w-[12rem] items-center truncate rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $badgeColor }}"
                                            title="{{ $log->action }}">
                                            {{ $log->action }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="text-sm font-semibold text-slate-800">
                                            {{ class_basename($log->model_type) }}
                                        </div>
                                        <div class="mt-0.5 font-mono text-xs text-slate-500">
                                            #{{ $log->model_id }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 sm:px-6">
                                        @if ($hasChanges)
                                            <details class="group max-w-md">
                                                <summary
                                                    class="btn-smooth inline-flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-indigo-100 bg-indigo-50/70 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100/80">
                                                    <i class="bi bi-code-slash"></i>
                                                    View changes
                                                    <i class="bi bi-chevron-down text-[10px] transition group-open:rotate-180"></i>
                                                </summary>

                                                <div class="mt-3 space-y-3">
                                                    @if (filled($oldValues))
                                                        <div>
                                                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-rose-600">
                                                                Before
                                                            </p>
                                                            <pre class="max-h-48 overflow-auto rounded-xl bg-rose-50/90 p-3 text-[11px] leading-relaxed text-rose-900 ring-1 ring-rose-100">{{ $oldValues }}</pre>
                                                        </div>
                                                    @endif

                                                    @if (filled($newValues))
                                                        <div>
                                                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-600">
                                                                After
                                                            </p>
                                                            <pre class="max-h-48 overflow-auto rounded-xl bg-emerald-50/90 p-3 text-[11px] leading-relaxed text-emerald-900 ring-1 ring-emerald-100">{{ $newValues }}</pre>
                                                        </div>
                                                    @endif
                                                </div>
                                            </details>
                                        @else
                                            <span class="text-xs font-medium text-slate-400">No payload</span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 sm:px-6">
                                        <span class="inline-flex items-center gap-1.5 font-mono text-xs text-slate-500">
                                            <i class="bi bi-globe2 text-slate-400"></i>
                                            {{ $log->ip_address ?? '—' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <div
                                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <i class="bi bi-journal-x text-xl"></i>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-slate-700">No audit logs found</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            @if ($hasActiveFilters)
                                                Try adjusting or clearing your filters.
                                            @else
                                                System activity will appear here as events are recorded.
                                            @endif
                                        </p>
                                        @if ($hasActiveFilters)
                                            <a href="{{ route('admin.audit-logs') }}"
                                                class="btn-smooth mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Clear filters
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="border-t border-white/60 bg-white/30 px-4 py-4 sm:px-6">
                        {{ $logs->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
