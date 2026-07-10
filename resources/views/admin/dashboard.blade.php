<x-app-layout>
    <x-slot name="header">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 p-6 sm:p-8 shadow-xl">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white"></div>
                <div class="absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-white"></div>
            </div>

            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white">
                        Administrator Dashboard
                    </h1>
                    <p class="mt-2 text-sm sm:text-base text-blue-100">
                        Platform overview — users, events, tickets, revenue, payments & support.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 sm:gap-3">
                    <a href="{{ route('admin.reports') }}"
                        class="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-2.5 sm:px-5 sm:py-3 text-sm font-semibold text-indigo-600 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5">
                        <i class="bi bi-graph-up-arrow"></i>
                        Full Reports
                    </a>
                    <a href="{{ route('admin.users') }}"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/30 bg-white/10 backdrop-blur-sm px-4 py-2.5 sm:px-5 sm:py-3 text-sm font-semibold text-white hover:bg-white/20 transition-all duration-300">
                        <i class="bi bi-people"></i>
                        Users
                    </a>
                    <a href="{{ route('admin.support-reports') }}"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/30 bg-white/10 backdrop-blur-sm px-4 py-2.5 sm:px-5 sm:py-3 text-sm font-semibold text-white hover:bg-white/20 transition-all duration-300">
                        <i class="bi bi-headset"></i>
                        Support
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $users = $dashboard['users'];
        $events = $dashboard['events'];
        $tickets = $dashboard['tickets'];
        $revenue = $dashboard['revenue'];
        $payments = $dashboard['payments'];
        $support = $dashboard['support'];
        $growth = $dashboard['userGrowthPercent'];
        $growthClass = $growth >= 0 ? 'text-emerald-600' : 'text-rose-600';
        $growthPrefix = $growth >= 0 ? '+' : '';
    @endphp

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Top KPI Summary --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6">
                @foreach ([
                    ['label' => 'Registered Users', 'value' => number_format($users['total']), 'sub' => $growthPrefix . $growth . '% vs last month', 'subClass' => $growthClass, 'icon' => 'bi-people', 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100'],
                    ['label' => 'Total Events', 'value' => number_format($events['total']), 'sub' => $events['ongoing'] . ' ongoing · ' . $events['completed'] . ' completed', 'subClass' => 'text-slate-500', 'icon' => 'bi-calendar-event', 'color' => 'text-blue-600', 'bg' => 'bg-blue-100'],
                    ['label' => 'Net Revenue', 'value' => 'LKR ' . number_format($revenue['net'], 0), 'sub' => 'Gross LKR ' . number_format($revenue['gross'], 0), 'subClass' => 'text-slate-500', 'icon' => 'bi-cash-stack', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100'],
                    ['label' => 'Tickets Sold', 'value' => number_format($tickets['sold']), 'sub' => $tickets['reserved'] . ' reserved in carts', 'subClass' => 'text-slate-500', 'icon' => 'bi-ticket-perforated', 'color' => 'text-cyan-600', 'bg' => 'bg-cyan-100'],
                ] as $kpi)
                    <div class="group rounded-3xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-500">{{ $kpi['label'] }}</p>
                                <h3 class="mt-1 text-2xl sm:text-3xl font-bold text-slate-900 truncate">{{ $kpi['value'] }}</h3>
                                <p class="mt-1.5 text-xs font-medium {{ $kpi['subClass'] }}">{{ $kpi['sub'] }}</p>
                            </div>
                            <div class="flex h-12 w-12 sm:h-14 sm:w-14 shrink-0 items-center justify-center rounded-2xl {{ $kpi['bg'] }} transition-transform duration-300 group-hover:scale-110">
                                <i class="bi {{ $kpi['icon'] }} text-xl sm:text-2xl {{ $kpi['color'] }}"></i>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Charts --}}
            <div class="rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-cyan-50 p-5 sm:p-6 shadow-sm">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-slate-900">Visual Analytics</h2>
                    <p class="mt-1 text-sm text-slate-500">User growth, revenue trends, and ticket sales by category</p>
                </div>
                <div class="grid gap-6 lg:grid-cols-3">
                    <x-report-chart-card title="User Growth" description="Monthly user registrations over the last 6 months" canvas-id="dashboardUserGrowthChart" />
                    <x-report-chart-card title="Revenue Trends" description="Monthly platform income from completed payments" canvas-id="dashboardRevenueChart" />
                    <x-report-chart-card title="Ticket Sales by Category" description="Confirmed ticket sales grouped by event category" canvas-id="dashboardTicketSalesChart" />
                </div>
            </div>

            {{-- Report Sections --}}
            <div class="grid gap-6 lg:grid-cols-2">

                {{-- 1. User Statistics --}}
                <section class="rounded-3xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:shadow-lg overflow-hidden">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100">
                                <i class="bi bi-people text-lg text-indigo-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">User Statistics</h3>
                                <p class="text-sm text-slate-500">Registered users, activity & roles</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4">
                            @foreach ([
                                ['Active Users', $users['active'], 'text-emerald-600'],
                                ['Inactive Users', $users['inactive'], 'text-slate-600'],
                                ['Verified', $users['verified'], 'text-blue-600'],
                                ['New This Month', $users['newThisMonth'], 'text-indigo-600'],
                            ] as [$label, $value, $color])
                                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition-colors hover:bg-indigo-50/50">
                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
                                    <p class="mt-1 text-2xl font-bold {{ $color }}">{{ number_format($value) }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Role Breakdown</p>
                            <div class="space-y-2">
                                @foreach ($users['byRole'] as $role)
                                    @php
                                        $pct = $users['total'] > 0 ? round(($role['count'] / $users['total']) * 100, 1) : 0;
                                    @endphp
                                    <div class="flex items-center gap-3">
                                        <span class="w-24 shrink-0 text-sm font-medium text-slate-700">{{ $role['label'] }}</span>
                                        <div class="flex-1 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-blue-500 transition-all duration-500" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="w-16 text-right text-sm font-semibold text-slate-900">{{ $role['count'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 2. Event Statistics --}}
                <section class="rounded-3xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:shadow-lg overflow-hidden">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-blue-50 to-white px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100">
                                <i class="bi bi-calendar-event text-lg text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Event Statistics</h3>
                                <p class="text-sm text-slate-500">Events, categories & lifecycle status</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4">
                            @foreach ([
                                ['Total Events', $events['total'], 'text-blue-600'],
                                ['Categories', $events['categories'], 'text-indigo-600'],
                                ['Ongoing', $events['ongoing'], 'text-cyan-600'],
                                ['Completed', $events['completed'], 'text-emerald-600'],
                            ] as [$label, $value, $color])
                                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition-colors hover:bg-blue-50/50">
                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
                                    <p class="mt-1 text-2xl font-bold {{ $color }}">{{ number_format($value) }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-5 grid grid-cols-3 gap-3">
                            @foreach ([
                                ['Upcoming', $events['upcoming'], 'bg-amber-50 text-amber-700 border-amber-100'],
                                ['Cancelled', $events['cancelled'], 'bg-rose-50 text-rose-700 border-rose-100'],
                                ['Unpublished', $events['unpublished'], 'bg-slate-100 text-slate-700 border-slate-200'],
                            ] as [$label, $value, $classes])
                                <div class="rounded-xl border px-3 py-2.5 text-center {{ $classes }}">
                                    <p class="text-xs font-medium">{{ $label }}</p>
                                    <p class="text-lg font-bold">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- 3. Ticket Statistics --}}
                <section class="rounded-3xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:shadow-lg overflow-hidden">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-cyan-50 to-white px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-100">
                                <i class="bi bi-ticket-perforated text-lg text-cyan-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Ticket Statistics</h3>
                                <p class="text-sm text-slate-500">Sales, cancellations & reservations</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4">
                            @foreach ([
                                ['Tickets Sold', $tickets['sold'], 'bi-check-circle', 'text-emerald-600', 'bg-emerald-50'],
                                ['Cancelled', $tickets['cancelled'], 'bi-x-circle', 'text-rose-600', 'bg-rose-50'],
                                ['Reserved', $tickets['reserved'], 'bi-clock-history', 'text-amber-600', 'bg-amber-50'],
                                ['Refunded', $tickets['refunded'], 'bi-arrow-counterclockwise', 'text-purple-600', 'bg-purple-50'],
                            ] as [$label, $value, $icon, $color, $bg])
                                <div class="flex items-center gap-4 rounded-2xl border border-slate-100 {{ $bg }} p-4 transition-transform hover:scale-[1.02]">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-sm">
                                        <i class="bi {{ $icon }} {{ $color }}"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
                                        <p class="text-xl font-bold {{ $color }}">{{ number_format($value) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-4 text-center text-xs text-slate-500">
                            {{ number_format($tickets['total']) }} total booking records on platform
                        </p>
                    </div>
                </section>

                {{-- 4. Revenue Statistics --}}
                <section class="rounded-3xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:shadow-lg overflow-hidden">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-emerald-50 to-white px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100">
                                <i class="bi bi-currency-dollar text-lg text-emerald-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Revenue Statistics</h3>
                                <p class="text-sm text-slate-500">Platform earnings & monthly breakdown</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-3 mb-5">
                            <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3 text-center">
                                <p class="text-xs text-emerald-700 font-medium">Gross</p>
                                <p class="text-sm sm:text-base font-bold text-emerald-800">LKR {{ number_format($revenue['gross'], 0) }}</p>
                            </div>
                            <div class="rounded-2xl bg-rose-50 border border-rose-100 p-3 text-center">
                                <p class="text-xs text-rose-700 font-medium">Refunded</p>
                                <p class="text-sm sm:text-base font-bold text-rose-800">LKR {{ number_format($revenue['refunded'], 0) }}</p>
                            </div>
                            <div class="rounded-2xl bg-indigo-50 border border-indigo-100 p-3 text-center">
                                <p class="text-xs text-indigo-700 font-medium">Net</p>
                                <p class="text-sm sm:text-base font-bold text-indigo-800">LKR {{ number_format($revenue['net'], 0) }}</p>
                            </div>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Monthly Breakdown</p>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            @foreach ($revenue['monthly'] as $month)
                                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-2.5 hover:bg-emerald-50/60 transition-colors">
                                    <span class="text-sm font-medium text-slate-700">{{ $month['month'] }}</span>
                                    <span class="text-sm font-bold text-emerald-700">LKR {{ number_format($month['amount'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- 5. Payment Statistics --}}
                <section class="rounded-3xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:shadow-lg overflow-hidden">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-violet-50 to-white px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100">
                                <i class="bi bi-credit-card text-lg text-violet-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Payment Statistics</h3>
                                <p class="text-sm text-slate-500">Completed, pending & refunded payments</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-3">
                            @foreach ([
                                ['Completed', $payments['completed'], 'text-emerald-600', 'bg-emerald-50 border-emerald-100'],
                                ['Pending', $payments['pending'], 'text-amber-600', 'bg-amber-50 border-amber-100'],
                                ['Refunded', $payments['refunded'], 'text-purple-600', 'bg-purple-50 border-purple-100'],
                            ] as [$label, $value, $color, $bg])
                                <div class="rounded-2xl border {{ $bg }} p-4 text-center transition-transform hover:scale-[1.03]">
                                    <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
                                    <p class="mt-1 text-2xl font-bold {{ $color }}">{{ number_format($value) }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="text-xs text-slate-500">Pending Amount</p>
                                <p class="text-sm font-bold text-amber-700">LKR {{ number_format($payments['pendingAmount'], 2) }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="text-xs text-slate-500">Failed / Cancelled</p>
                                <p class="text-sm font-bold text-slate-700">{{ $payments['failed'] }} / {{ $payments['cancelled'] }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 6. Support Statistics --}}
                <section class="rounded-3xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:shadow-lg overflow-hidden">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-amber-50 to-white px-6 py-5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100">
                                    <i class="bi bi-headset text-lg text-amber-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">Support Statistics</h3>
                                    <p class="text-sm text-slate-500">Requests handled by CROs</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.support-reports') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">View all →</a>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                            @foreach ([
                                ['Total', $support['totalRequests'], 'text-slate-900'],
                                ['Pending', $support['pending'], 'text-amber-600'],
                                ['Resolved', $support['resolved'], 'text-emerald-600'],
                                ['Inquiries', $support['inquiries'], 'text-indigo-600'],
                            ] as [$label, $value, $color])
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-center">
                                    <p class="text-xs text-slate-500">{{ $label }}</p>
                                    <p class="text-lg font-bold {{ $color }}">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>

                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">CRO Performance</p>
                        <div class="space-y-2 max-h-52 overflow-y-auto">
                            @forelse ($support['croPerformance'] as $cro)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 hover:bg-amber-50/50 transition-colors">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900 truncate">{{ $cro['name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $cro['totalAssigned'] }} assigned · {{ $cro['active'] }} active</p>
                                    </div>
                                    <div class="shrink-0 rounded-lg bg-emerald-100 px-3 py-1">
                                        <span class="text-sm font-bold text-emerald-700">{{ $cro['handled'] }}</span>
                                        <span class="text-xs text-emerald-600"> handled</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-sm text-slate-500 py-4">No CRO staff registered yet.</p>
                            @endforelse
                        </div>
                    </div>
                </section>

            </div>

            {{-- Recent Activity & Quick Actions --}}
            <div class="grid gap-6 xl:grid-cols-3">
                <div class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:shadow-lg">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h3 class="text-lg font-bold text-slate-900">Recent Activity</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Latest platform audit log entries</p>
                    </div>
                    <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                        @forelse ($dashboard['recentActivity'] as $log)
                            <div class="flex items-start gap-4 px-6 py-4 hover:bg-slate-50 transition-colors">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100">
                                    <i class="bi bi-activity text-indigo-600 text-sm"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-900">
                                        {{ $log['user'] }} — {{ $log['action'] }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        {{ $log['model'] }} · {{ $log['time'] }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="px-6 py-10 text-center text-slate-500">No recent activity recorded.</p>
                        @endforelse
                    </div>
                </div>

                <aside class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h3 class="text-lg font-bold text-slate-900">Quick Actions</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Frequently used admin tools</p>
                    </div>
                    <div class="p-4 space-y-3">
                        @foreach ([
                            ['route' => 'admin.reports', 'title' => 'Reports & Analytics', 'desc' => 'Full platform insights & exports', 'icon' => 'bi-graph-up'],
                            ['route' => 'admin.users', 'title' => 'Manage Users', 'desc' => 'Create, edit & monitor accounts', 'icon' => 'bi-people'],
                            ['route' => 'admin.event-categories', 'title' => 'Event Categories', 'desc' => 'Manage platform categories', 'icon' => 'bi-tags'],
                            ['route' => 'admin.audit-logs', 'title' => 'Audit Logs', 'desc' => 'Review system activity history', 'icon' => 'bi-journal-text'],
                            ['route' => 'admin.support-reports', 'title' => 'Support Reports', 'desc' => $support['resolved'] . ' resolved requests', 'icon' => 'bi-headset'],
                        ] as $action)
                            <a href="{{ route($action['route']) }}"
                                class="group flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition-all duration-300 hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-50/50 hover:shadow-md">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white border border-slate-200 group-hover:border-indigo-200 transition-colors">
                                    <i class="bi {{ $action['icon'] }} text-indigo-600"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">{{ $action['title'] }}</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $action['desc'] }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </aside>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            window.adminDashboardData = @json($dashboard);
        </script>
        @vite('resources/js/admin-dashboard.js')
    @endpush
</x-app-layout>
