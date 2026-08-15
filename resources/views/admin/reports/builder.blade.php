<x-app-layout>
    @php
        $catalogJson = collect($catalog)->map(fn ($report) => [
            'key' => $report['key'],
            'label' => $report['label'],
            'description' => $report['description'],
            'formats' => $report['formats'],
            'fields' => $report['fields'],
            'filters' => $report['filters'],
            'kind' => $report['kind'],
            'is_analytics' => $report['is_analytics'],
            'skips_fields' => $report['skips_fields'],
        ])->values();
    @endphp

    <div class="bg-slate-100/80 py-6 sm:py-8"
        x-data="adminReportBuilder({
            catalog: @js($catalogJson),
            defaultReport: @js($oldReport),
            oldFields: @js($oldFields),
            oldFilters: @js($oldFilters),
            oldFormat: @js($oldFormat),
        })">

        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">

            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Reports</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Choose a report, pick columns and filters, then download as PDF or CSV.
                    </p>
                </div>
                <a href="{{ route('dashboard') }}"
                    class="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">
                    Back to dashboard
                </a>
            </div>

            @if ($errors->any())
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Could not generate the report</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="admin-report-form"
                method="POST"
                action="{{ route('admin.reports.generate') }}"
                data-chart-data-url="{{ route('admin.reports.chart-data') }}"
                class="space-y-4">
                @csrf
                <input type="hidden" name="report" :value="selectedKey">
                <input type="hidden" name="format" :value="format">

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-base font-bold text-slate-900">1. Select report</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Pick the dataset you want to export</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <template x-for="report in catalog" :key="report.key">
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-lg border px-4 py-3.5 transition"
                                :class="selectedKey === report.key
                                    ? 'border-blue-400 bg-blue-50/80 ring-1 ring-blue-200'
                                    : 'border-slate-200 bg-white hover:border-slate-300'">
                                <input
                                    type="radio"
                                    class="mt-0.5 h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500"
                                    name="report_ui"
                                    :value="report.key"
                                    x-model="selectedKey"
                                    @change="onReportChange()">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-slate-900"
                                        x-text="report.label"></span>
                                    <span class="mt-0.5 block text-xs leading-relaxed text-slate-500"
                                        x-text="report.description"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </section>

                <template x-if="!current.skips_fields">
                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 class="text-base font-bold text-slate-900">2. Select fields</h2>
                                <p class="mt-0.5 text-sm text-slate-500">Choose columns included in the export</p>
                            </div>
                            <div class="flex items-center gap-4 text-sm font-medium">
                                <button type="button" class="text-blue-600 hover:underline" @click="selectAllFields()">Select all</button>
                                <button type="button" class="text-slate-500 hover:underline" @click="clearFields()">Clear</button>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                            <template x-for="(label, key) in current.fields" :key="key">
                                <label class="flex items-center gap-2.5 rounded-md border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        name="fields[]"
                                        :value="key"
                                        x-model="fields">
                                    <span x-text="label"></span>
                                </label>
                            </template>
                        </div>
                    </section>
                </template>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-base font-bold text-slate-900">
                        <span x-text="current.skips_fields ? '2' : '3'"></span>. Apply filters
                    </h2>
                    <p class="mt-0.5 text-sm text-slate-500">Narrow the dataset before download</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <template x-for="filter in visibleFilters" :key="filter.key">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700"
                                    x-text="filter.label + (filter.required ? ' *' : '')"></label>

                                <template x-if="filter.type === 'text'">
                                    <input
                                        type="text"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        :name="'filters[' + filter.key + ']'"
                                        x-model="filters[filter.key]"
                                        placeholder="Search">
                                </template>

                                <template x-if="filter.type === 'date'">
                                    <input
                                        type="date"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        :name="'filters[' + filter.key + ']'"
                                        x-model="filters[filter.key]">
                                </template>

                                <template x-if="filter.type === 'select'">
                                    <select
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        :name="'filters[' + filter.key + ']'"
                                        x-model="filters[filter.key]"
                                        @change="onFilterChange(filter)">
                                        <option
                                            value=""
                                            :hidden="filter.include_empty === false"
                                            :disabled="filter.include_empty === false"
                                            x-text="filter.required ? 'Select…' : 'All'"></option>
                                        <template x-for="(optLabel, optKey) in optionsFor(filter)" :key="optKey">
                                            <option :value="optKey" x-text="optLabel"></option>
                                        </template>
                                    </select>
                                </template>
                            </div>
                        </template>

                        <template x-if="visibleFilters.length === 0">
                            <p class="text-sm text-slate-500 sm:col-span-2 lg:col-span-3">No filters for this report.</p>
                        </template>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-base font-bold text-slate-900">
                        <span x-text="current.skips_fields ? '3' : '4'"></span>. Select format
                    </h2>
                    <p class="mt-0.5 text-sm text-slate-500">Choose how the file is downloaded</p>

                    <div class="mt-4 flex flex-wrap items-center gap-6">
                        <template x-for="fmt in current.formats" :key="fmt">
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                                <input
                                    type="radio"
                                    class="h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-500"
                                    name="format_ui"
                                    :value="fmt"
                                    x-model="format">
                                <span class="uppercase tracking-wide" x-text="fmt"></span>
                            </label>
                        </template>
                    </div>
                </section>

                <div class="flex justify-end pb-4">
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Generate Report
                    </button>
                </div>
            </form>

            <div id="admin-analytics-export-charts"
                class="pointer-events-none fixed top-0 w-[900px]"
                style="left: -10000px;"
                aria-hidden="true">
                @foreach ([
                    'dashboardTopEventsChart',
                    'dashboardConversionFunnelChart',
                    'adminSupportVolumeChart',
                    'adminSupportSlaChart',
                    'adminOverviewUserGrowthChart',
                    'adminOverviewUserDistributionChart',
                    'adminOverviewRevenueTrendChart',
                    'adminOverviewTicketSalesChart',
                    'adminOverviewEventsByCategoryChart',
                    'adminEventsStatusChart',
                    'adminPlatformGrowthChart',
                    'adminTopCategoriesChart',
                    'userStatusChart',
                    'userRoleChart',
                    'userRegistrationChart',
                    'paymentStatusChart',
                    'paymentMethodChart',
                    'paymentRevenueChart',
                ] as $canvasId)
                    <div class="h-64 w-full">
                        <canvas id="{{ $canvasId }}"></canvas>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        function adminReportBuilder({ catalog, defaultReport, oldFields, oldFilters, oldFormat }) {
            const byKey = Object.fromEntries(catalog.map((r) => [r.key, r]));
            const initialKey = byKey[defaultReport] ? defaultReport : (catalog[0]?.key ?? null);

            const defaultFiltersFor = (key) => {
                if (key === 'insights_analytics') {
                    return { section: 'all' };
                }
                return {};
            };

            return {
                catalog,
                byKey,
                selectedKey: initialKey,
                fields: Array.isArray(oldFields) && oldFields.length
                    ? oldFields
                    : (byKey[initialKey]?.skips_fields ? [] : Object.keys(byKey[initialKey]?.fields ?? {})),
                filters: Object.keys(oldFilters || {}).length
                    ? { ...(oldFilters || {}) }
                    : defaultFiltersFor(initialKey),
                format: oldFormat || (byKey[initialKey]?.formats?.[0] ?? 'pdf'),

                get current() {
                    return byKey[this.selectedKey] || {
                        fields: {},
                        filters: [],
                        formats: ['pdf'],
                        skips_fields: true,
                    };
                },

                get visibleFilters() {
                    return (this.current.filters || []).filter((filter) => {
                        if (filter.hide_when) {
                            const hidden = Object.entries(filter.hide_when).every(
                                ([key, expected]) => String(this.filters[key] ?? '') === String(expected)
                            );
                            if (hidden) return false;
                        }
                        if (!filter.show_when) return true;
                        return Object.entries(filter.show_when).every(
                            ([key, expected]) => String(this.filters[key] ?? '') === String(expected)
                        );
                    });
                },

                scopeKeyFor(filter) {
                    if (Array.isArray(filter?.scope_by_when)) {
                        for (const rule of filter.scope_by_when) {
                            const when = rule.when || {};
                            const matches = Object.entries(when).every(
                                ([key, expected]) => String(this.filters[key] ?? '') === String(expected)
                            );
                            if (matches) return rule.scope_by;
                        }
                    }

                    return filter?.scope_by || null;
                },

                optionsFor(filter) {
                    const options = filter?.options || {};
                    const scopeBy = this.scopeKeyFor(filter);
                    if (!scopeBy) return options;

                    const maps = filter?.option_scope_maps || {};
                    const scopes = maps[scopeBy] || filter?.option_scopes || {};
                    const scopeValue = String(this.filters[scopeBy] ?? '');
                    if (!scopeValue) return options;

                    const filtered = {};
                    Object.keys(options).forEach((id) => {
                        if (String(scopes[id] ?? '') === scopeValue) {
                            filtered[id] = options[id];
                        }
                    });

                    return filtered;
                },

                syncEventToScope() {
                    const eventId = String(this.filters.event_id ?? '');
                    if (!eventId) return;

                    const eventFilter = (this.current.filters || []).find((filter) => filter.key === 'event_id');
                    if (!eventFilter) return;

                    const remaining = this.optionsFor(eventFilter);
                    if (!Object.prototype.hasOwnProperty.call(remaining, eventId)) {
                        this.filters.event_id = '';
                    }
                },

                onFilterChange(filter) {
                    if (filter?.key === 'section') {
                        if (this.filters.section === 'support') {
                            this.filters.organizer_id = '';
                        } else {
                            this.filters.cro_id = '';
                        }
                    }

                    if (['organizer_id', 'cro_id', 'section'].includes(filter?.key)) {
                        this.syncEventToScope();
                    }
                },

                onReportChange() {
                    this.fields = this.current.skips_fields ? [] : Object.keys(this.current.fields || {});
                    this.filters = defaultFiltersFor(this.selectedKey);
                    if (!this.current.formats.includes(this.format)) {
                        this.format = this.current.formats[0] || 'pdf';
                    }
                },

                selectAllFields() {
                    this.fields = Object.keys(this.current.fields || {});
                },

                clearFields() {
                    this.fields = [];
                },
            };
        }
    </script>
    @vite('resources/js/admin-report-builder.js')
</x-app-layout>
