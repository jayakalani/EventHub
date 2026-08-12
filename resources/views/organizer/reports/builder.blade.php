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

    <div class="relative isolate overflow-hidden py-5 sm:py-6"
        x-data="organizerReportBuilder({
            catalog: @js($catalogJson),
            defaultReport: @js($oldReport),
            oldFields: @js($oldFields),
            oldFilters: @js($oldFilters),
            oldFormat: @js($oldFormat),
        })">

        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/20 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/15 blur-3xl"></div>
        </div>

        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">

            <section class="rounded-2xl border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur-xl sm:p-6">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Organizer</p>
                <h1 class="mt-0.5 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Reports</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Choose a report, pick columns and filters, then download as PDF or CSV.
                    Visual analytics stay on
                    <a href="{{ route('organizer.dashboard') }}#insights" class="font-medium text-indigo-600 hover:underline">Insights</a>.
                </p>
            </section>

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Could not generate the report</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('organizer.reports.generate') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="report" :value="selectedKey">
                <input type="hidden" name="format" :value="format">

                <section class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm backdrop-blur-xl sm:p-6">
                    <h2 class="text-base font-bold text-slate-900">1. Select report</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Pick the dataset you want to export</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <template x-for="report in catalog" :key="report.key">
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                                :class="selectedKey === report.key
                                    ? 'border-indigo-400 bg-indigo-50/70 ring-1 ring-indigo-200'
                                    : 'border-slate-200 bg-white hover:border-slate-300'">
                                <input
                                    type="radio"
                                    class="mt-1 h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    name="report_ui"
                                    :value="report.key"
                                    x-model="selectedKey"
                                    @change="onReportChange()">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-slate-900" x-text="report.label"></span>
                                    <span class="mt-0.5 block text-xs text-slate-500" x-text="report.description"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </section>

                <template x-if="!current.skips_fields">
                    <section class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm backdrop-blur-xl sm:p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 class="text-base font-bold text-slate-900">2. Select fields</h2>
                                <p class="mt-0.5 text-sm text-slate-500">Choose columns included in the export</p>
                            </div>
                            <div class="flex items-center gap-3 text-sm font-medium">
                                <button type="button" class="text-indigo-600 hover:underline" @click="selectAllFields()">Select all</button>
                                <button type="button" class="text-slate-500 hover:underline" @click="clearFields()">Clear</button>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <template x-for="(label, key) in current.fields" :key="key">
                                <label class="flex items-center gap-2 rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        name="fields[]"
                                        :value="key"
                                        x-model="fields">
                                    <span x-text="label"></span>
                                </label>
                            </template>
                        </div>
                    </section>
                </template>

                <section class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm backdrop-blur-xl sm:p-6">
                    <h2 class="text-base font-bold text-slate-900">
                        <span x-text="current.skips_fields ? '2' : '3'"></span>. Apply filters
                    </h2>
                    <p class="mt-0.5 text-sm text-slate-500">Narrow the dataset before download</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <template x-for="filter in visibleFilters" :key="filter.key">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500"
                                    x-text="filter.label + (filter.required ? ' *' : '')"></label>

                                <template x-if="filter.type === 'text'">
                                    <input
                                        type="text"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                        :name="'filters[' + filter.key + ']'"
                                        x-model="filters[filter.key]"
                                        placeholder="Search…">
                                </template>

                                <template x-if="filter.type === 'date'">
                                    <input
                                        type="date"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                        :name="'filters[' + filter.key + ']'"
                                        x-model="filters[filter.key]">
                                </template>

                                <template x-if="filter.type === 'select'">
                                    <select
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                        :name="'filters[' + filter.key + ']'"
                                        x-model="filters[filter.key]">
                                        <option value="">All</option>
                                        <template x-for="(optLabel, optKey) in filter.options" :key="optKey">
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

                <section class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm backdrop-blur-xl sm:p-6">
                    <h2 class="text-base font-bold text-slate-900">
                        <span x-text="current.skips_fields ? '3' : '4'"></span>. Select format
                    </h2>
                    <p class="mt-0.5 text-sm text-slate-500">Choose how the file is downloaded</p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <template x-for="fmt in current.formats" :key="fmt">
                            <label
                                class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition"
                                :class="format === fmt
                                    ? 'border-indigo-400 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'">
                                <input
                                    type="radio"
                                    class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    name="format_ui"
                                    :value="fmt"
                                    x-model="format">
                                <span class="uppercase" x-text="fmt"></span>
                            </label>
                        </template>
                    </div>
                </section>

                <div class="flex justify-end pb-8">
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <i class="bi bi-download"></i>
                        Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function organizerReportBuilder({ catalog, defaultReport, oldFields, oldFilters, oldFormat }) {
            const byKey = Object.fromEntries(catalog.map((r) => [r.key, r]));
            const initialKey = byKey[defaultReport] ? defaultReport : (catalog[0]?.key ?? null);

            return {
                catalog,
                selectedKey: initialKey,
                fields: Array.isArray(oldFields) && oldFields.length
                    ? oldFields
                    : (byKey[initialKey]?.skips_fields ? [] : Object.keys(byKey[initialKey]?.fields ?? {})),
                filters: Object.keys(oldFilters || {}).length
                    ? { ...(oldFilters || {}) }
                    : (initialKey === 'insights_analytics' ? { period: 'month' } : {}),
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
                        if (!filter.show_when) return true;
                        return Object.entries(filter.show_when).every(
                            ([key, expected]) => String(this.filters[key] ?? '') === String(expected)
                        );
                    });
                },

                onReportChange() {
                    this.fields = this.current.skips_fields ? [] : Object.keys(this.current.fields || {});
                    this.filters = {};
                    if (this.current.key === 'insights_analytics') {
                        this.filters.period = 'month';
                    }
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
</x-app-layout>
