<x-app-layout>
    <x-slot name="header">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 p-8 shadow-xl">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white"></div>
                <div class="absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-white"></div>
            </div>

            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">CRO Reports</h1>
                    <p class="mt-2 text-blue-100">
                        Track inquiry resolution performance and complaint statistics.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('cro.dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/30 bg-white/10 backdrop-blur-sm px-5 py-3 text-sm font-semibold text-white hover:bg-white/20 transition-all duration-300">
                        <i class="bi bi-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <a href="{{ route('cro.inquiries.index') }}"
                        class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-indigo-600 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <i class="bi bi-chat-left-text"></i>
                        Manage Inquiries
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $inquiries = $reports['inquiries'];
        $complaints = $reports['complaints'];
        $validTabs = ['inquiries', 'complaints'];
        $initialTab = in_array(request('tab'), $validTabs, true) ? request('tab') : 'inquiries';
    @endphp

    <div class="py-8" x-data="{ activeTab: '{{ $initialTab }}' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Key Visual Insights --}}
            <div class="rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-cyan-50 p-6 shadow-sm">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Key Visual Insights</h2>
                        <p class="mt-1 text-sm text-slate-500">Inquiry resolution and complaint submission trends</p>
                    </div>
                    <x-report-export-buttons excel-route="cro.reports.export.excel" pdf-route="cro.reports.export.pdf" section="inquiries" />
                </div>
                <div class="grid gap-6 lg:grid-cols-2">
                    <x-report-chart-card title="Resolution Rate Trend" description="Monthly inquiry resolution percentage" canvas-id="overviewResolutionRateChart" />
                    <x-report-chart-card title="Complaint Submissions" description="New complaints over the last 6 months" canvas-id="overviewComplaintTrendChart" />
                </div>
            </div>

            {{-- Tab Navigation --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach ([
                        'inquiries' => ['label' => 'Inquiry Resolution', 'icon' => 'bi-chat-left-text', 'desc' => 'Resolution rates & trends'],
                        'complaints' => ['label' => 'Complaint Statistics', 'icon' => 'bi-exclamation-triangle', 'desc' => 'Status & type breakdown'],
                    ] as $key => $tab)
                        <button type="button"
                            @click="activeTab = '{{ $key }}'; $nextTick(() => window.dispatchEvent(new CustomEvent('cro-reports-tab-changed')))"
                            :class="activeTab === '{{ $key }}'
                                ? 'bg-gradient-to-br from-indigo-600 to-blue-600 text-white shadow-lg shadow-indigo-200'
                                : 'bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                            class="group rounded-2xl p-4 text-left transition-all duration-300 hover:-translate-y-0.5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl"
                                    :class="activeTab === '{{ $key }}' ? 'bg-white/20' : 'bg-white border border-slate-200'">
                                    <i class="bi {{ $tab['icon'] }} text-lg"
                                        :class="activeTab === '{{ $key }}' ? 'text-white' : 'text-indigo-600'"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold truncate">{{ $tab['label'] }}</p>
                                    <p class="text-xs truncate"
                                        :class="activeTab === '{{ $key }}' ? 'text-blue-100' : 'text-slate-500'">
                                        {{ $tab['desc'] }}
                                    </p>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Inquiry Resolution Report --}}
            <div x-show="activeTab === 'inquiries'" x-cloak class="space-y-8">
                <x-report-section-header title="Inquiry Resolution Report" description="Track how well inquiries are handled">
                    <x-report-export-buttons excel-route="cro.reports.export.excel" pdf-route="cro.reports.export.pdf" section="inquiries" />
                </x-report-section-header>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach ([
                        ['label' => 'Total Inquiries', 'value' => $inquiries['total'], 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'icon' => 'bi-inbox'],
                        ['label' => 'Resolution Rate', 'value' => $inquiries['resolutionRate'] . '%', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100', 'icon' => 'bi-check2-circle'],
                        ['label' => 'Active', 'value' => $inquiries['active'], 'color' => 'text-amber-600', 'bg' => 'bg-amber-100', 'icon' => 'bi-hourglass-split'],
                        ['label' => 'Resolved / Closed', 'value' => $inquiries['resolvedOrClosed'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-patch-check'],
                    ] as $card)
                        <div class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                                    <h3 class="mt-2 text-3xl font-bold text-slate-900">{{ $card['value'] }}</h3>
                                </div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $card['bg'] }}">
                                    <i class="bi {{ $card['icon'] }} text-xl {{ $card['color'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach ($inquiries['statusBreakdown'] as $status)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <p class="text-2xl font-bold text-slate-900">{{ $status['count'] }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-500">{{ $status['label'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-6 xl:grid-cols-3">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Status Distribution</h3>
                        <p class="mt-1 text-sm text-slate-500">Open, in progress, resolved, and closed</p>
                        <div class="mt-6 h-72">
                            <canvas id="inquiryStatusChart"></canvas>
                        </div>
                    </div>

                    <div class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Resolution Activity</h3>
                        <p class="mt-1 text-sm text-slate-500">Inquiries submitted vs resolved over the last 6 months</p>
                        <div class="mt-6 h-72">
                            <canvas id="inquiryResolutionTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Resolution Rate Trend</h3>
                        <p class="mt-1 text-sm text-slate-500">Monthly resolution rate percentage</p>
                        <div class="mt-6 h-64">
                            <canvas id="inquiryResolutionRateChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Inquiries by Event</h3>
                        <p class="mt-1 text-sm text-slate-500">Events generating the most inquiries</p>
                        <div class="mt-6 h-64">
                            <canvas id="inquiryByEventChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 px-6 py-5">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Recent Inquiries</h3>
                            <p class="mt-1 text-sm text-slate-500">Latest submissions and their resolution status</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-report-export-buttons excel-route="cro.reports.export.excel" pdf-route="cro.reports.export.pdf" section="inquiries" />
                            <a href="{{ route('cro.inquiries.index') }}"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-100">
                                View All
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Subject</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 hidden md:table-cell">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell">Event</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">When</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($inquiries['recentInquiries'] as $inquiry)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900 max-w-xs truncate">{{ $inquiry['subject'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-500 hidden md:table-cell whitespace-nowrap">{{ $inquiry['user'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-500 hidden sm:table-cell max-w-xs truncate">{{ $inquiry['event'] }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $inquiry['statusClass'] }}">
                                                {{ $inquiry['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-400 text-right whitespace-nowrap">{{ $inquiry['submitted'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">No inquiries yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Complaint Statistics --}}
            <div x-show="activeTab === 'complaints'" x-cloak class="space-y-8">
                <x-report-section-header title="Complaint Statistics" description="Counts by status and complaint type">
                    <x-report-export-buttons excel-route="cro.reports.export.excel" pdf-route="cro.reports.export.pdf" section="complaints" />
                </x-report-section-header>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach ([
                        ['label' => 'Total Complaints', 'value' => $complaints['total'], 'color' => 'text-rose-600', 'bg' => 'bg-rose-100', 'icon' => 'bi-exclamation-octagon'],
                        ['label' => 'Open', 'value' => $complaints['open'], 'color' => 'text-amber-600', 'bg' => 'bg-amber-100', 'icon' => 'bi-envelope-open'],
                        ['label' => 'In Progress', 'value' => $complaints['inProgress'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-arrow-repeat'],
                        ['label' => 'Resolved / Closed', 'value' => $complaints['resolved'] + $complaints['closed'], 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100', 'icon' => 'bi-check-circle'],
                    ] as $card)
                        <div class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                                    <h3 class="mt-2 text-3xl font-bold text-slate-900">{{ $card['value'] }}</h3>
                                </div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $card['bg'] }}">
                                    <i class="bi {{ $card['icon'] }} text-xl {{ $card['color'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-6 xl:grid-cols-3">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Complaints by Status</h3>
                        <p class="mt-1 text-sm text-slate-500">Current status distribution</p>
                        <div class="mt-6 h-72">
                            <canvas id="complaintStatusChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Complaints by Type</h3>
                        <p class="mt-1 text-sm text-slate-500">Categorized by subject theme</p>
                        <div class="mt-6 h-72">
                            <canvas id="complaintTypeChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Submission Trend</h3>
                        <p class="mt-1 text-sm text-slate-500">New complaints over the last 6 months</p>
                        <div class="mt-6 h-72">
                            <canvas id="complaintSubmissionsChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-900">Status Breakdown by Type</h3>
                    <p class="mt-1 text-sm text-slate-500">How each complaint category is being handled</p>
                    <div class="mt-6 h-80">
                        <canvas id="complaintStatusByTypeChart"></canvas>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 px-6 py-5">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Recent Complaints</h3>
                            <p class="mt-1 text-sm text-slate-500">Latest complaints with type and status</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-report-export-buttons excel-route="cro.reports.export.excel" pdf-route="cro.reports.export.pdf" section="complaints" />
                            <a href="{{ route('cro.complaints.index') }}"
                                class="inline-flex items-center gap-2 rounded-xl bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-100">
                                View All
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Subject</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 hidden md:table-cell">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">When</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($complaints['recentComplaints'] as $complaint)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900 max-w-xs truncate">{{ $complaint['subject'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-500 hidden md:table-cell whitespace-nowrap">{{ $complaint['user'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-500 hidden sm:table-cell">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                                {{ $complaint['type'] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $complaint['statusClass'] }}">
                                                {{ $complaint['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-400 text-right whitespace-nowrap">{{ $complaint['submitted'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">No complaints yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('styles')
        <style>
            [x-cloak] { display: none !important; }
        </style>
    @endpush

    @push('scripts')
        <script>
            window.croReportData = @json($reports);
        </script>
        @vite('resources/js/cro-reports.js')
    @endpush
</x-app-layout>
