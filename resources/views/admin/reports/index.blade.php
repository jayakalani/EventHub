<x-app-layout>
    <x-slot name="header">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 p-8 shadow-xl">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white"></div>
                <div class="absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-white"></div>
            </div>

            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">Reports & Analytics</h1>
                    <p class="mt-2 text-blue-100">
                        Platform-wide insights across users, payments, and system activity.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/30 bg-white/10 backdrop-blur-sm px-5 py-3 text-sm font-semibold text-white hover:bg-white/20 transition-all duration-300">
                        <i class="bi bi-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $admin = $reports['admin'];
        $users = $reports['users'];
        $payments = $reports['payments'];
        $system = $reports['system'];
    @endphp

    <div class="py-8" x-data="{ activeTab: 'admin' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Key Visual Insights --}}
            <div class="rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-cyan-50 p-6 shadow-sm">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Key Visual Insights</h2>
                        <p class="mt-1 text-sm text-slate-500">Revenue, ticket sales, and user registration trends at a glance</p>
                    </div>
                    <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" section="admin" />
                </div>
                <div class="grid gap-6 lg:grid-cols-3">
                    <x-report-chart-card title="Revenue Trends" description="Monthly completed payment revenue" canvas-id="overviewRevenueChart" />
                    <x-report-chart-card title="Ticket Sales Trends" description="Monthly tickets sold across the platform" canvas-id="overviewTicketSalesChart" />
                    <x-report-chart-card title="User Registrations" description="New user sign-ups over 6 months" canvas-id="overviewUserRegChart" />
                </div>
            </div>

            {{-- Tab Navigation --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ([
                        'admin' => ['label' => 'Admin Reports', 'icon' => 'bi-speedometer2', 'desc' => 'Platform overview'],
                        'users' => ['label' => 'User Reports', 'icon' => 'bi-people', 'desc' => 'User insights'],
                        'payments' => ['label' => 'Payment Reports', 'icon' => 'bi-credit-card', 'desc' => 'Sales & revenue'],
                        'system' => ['label' => 'System Reports', 'icon' => 'bi-activity', 'desc' => 'Usage & logs'],
                    ] as $key => $tab)
                        <button type="button"
                            @click="activeTab = '{{ $key }}'; $nextTick(() => window.dispatchEvent(new CustomEvent('admin-reports-tab-changed')))"
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

            {{-- Admin Reports --}}
            <div x-show="activeTab === 'admin'" x-cloak class="space-y-8">
                <x-report-section-header title="Admin Reports" description="Platform-wide summary and oversight">
                    <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" section="admin" />
                </x-report-section-header>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach ([
                        ['label' => 'Total Users', 'value' => $admin['totalUsers'], 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'icon' => 'bi-people'],
                        ['label' => 'Total Events', 'value' => $admin['totalEvents'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-calendar-event'],
                        ['label' => 'Tickets Sold', 'value' => $admin['totalTicketsSold'], 'color' => 'text-cyan-600', 'bg' => 'bg-cyan-100', 'icon' => 'bi-ticket-perforated'],
                        ['label' => 'Net Revenue', 'value' => 'LKR ' . number_format($admin['netRevenue'], 2), 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100', 'icon' => 'bi-cash-stack'],
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
                    <div class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Platform Growth</h3>
                        <p class="mt-1 text-sm text-slate-500">New users and events over the last 6 months</p>
                        <div class="mt-6 h-72">
                            <canvas id="adminPlatformGrowthChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Events by Status</h3>
                        <p class="mt-1 text-sm text-slate-500">Current event distribution</p>
                        <div class="mt-6 h-72">
                            <canvas id="adminEventsStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Top Event Categories</h3>
                        <p class="mt-1 text-sm text-slate-500">Most popular categories by event count</p>
                        <div class="mt-6 h-64">
                            <canvas id="adminTopCategoriesChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Platform Summary</h3>
                        <p class="mt-1 text-sm text-slate-500">Key oversight metrics</p>
                        <dl class="mt-6 space-y-4">
                            @foreach ([
                                ['Hosts', $admin['totalHosts']],
                                ['Categories', $admin['totalCategories']],
                                ['Gross Revenue', 'LKR ' . number_format($admin['totalRevenue'], 2)],
                                ['Refunds Deducted', 'LKR ' . number_format($admin['totalRevenue'] - $admin['netRevenue'], 2)],
                            ] as [$label, $value])
                                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 transition hover:bg-slate-100">
                                    <dt class="text-sm font-medium text-slate-600">{{ $label }}</dt>
                                    <dd class="text-sm font-bold text-slate-900">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            </div>

            {{-- User Reports --}}
            <div x-show="activeTab === 'users'" x-cloak class="space-y-8">
                <x-report-section-header title="User Reports" description="System user information and registration trends">
                    <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" section="users" />
                </x-report-section-header>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach ([
                        ['label' => 'Total Users', 'value' => $users['totalUsers'], 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'icon' => 'bi-people-fill'],
                        ['label' => 'Active Users', 'value' => $users['activeUsers'], 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100', 'icon' => 'bi-person-check'],
                        ['label' => 'Verified', 'value' => $users['verifiedUsers'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-patch-check'],
                        ['label' => 'New This Month', 'value' => $users['newUsersThisMonth'], 'color' => 'text-cyan-600', 'bg' => 'bg-cyan-100', 'icon' => 'bi-person-plus'],
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
                        <h3 class="text-lg font-bold text-slate-900">Users by Role</h3>
                        <p class="mt-1 text-sm text-slate-500">Role distribution across the platform</p>
                        <div class="mt-6 h-72">
                            <canvas id="userRoleChart"></canvas>
                        </div>
                    </div>

                    <div class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Registration Trend</h3>
                        <p class="mt-1 text-sm text-slate-500">New user sign-ups over the last 6 months</p>
                        <div class="mt-6 h-72">
                            <canvas id="userRegistrationChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Account Status Breakdown</h3>
                        <p class="mt-1 text-sm text-slate-500">Active, verified, and locked accounts</p>
                        <div class="mt-6 h-64">
                            <canvas id="userStatusChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Recent Users</h3>
                                <p class="mt-1 text-sm text-slate-500">Latest registrations</p>
                            </div>
                            <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" section="users" />
                        </div>
                        <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                            @forelse($users['recentUsers'] as $user)
                                <div class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900 truncate">{{ $user['name'] }}</p>
                                        <p class="text-sm text-slate-500 truncate">{{ $user['email'] }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                            {{ ucfirst($user['role']) }}
                                        </span>
                                        <p class="mt-1 text-xs text-slate-400">{{ $user['joined'] }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="px-6 py-8 text-center text-slate-500">No users found.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Reports --}}
            <div x-show="activeTab === 'payments'" x-cloak class="space-y-8">
                <x-report-section-header title="Payment Reports" description="Ticket sales, refunds, and revenue summaries">
                    <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" section="payments" />
                </x-report-section-header>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach ([
                        ['label' => 'Total Revenue', 'value' => 'LKR ' . number_format($payments['totalRevenue'], 2), 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100', 'icon' => 'bi-graph-up-arrow'],
                        ['label' => 'Net Revenue', 'value' => 'LKR ' . number_format($payments['netRevenue'], 2), 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'icon' => 'bi-wallet2'],
                        ['label' => 'Tickets Sold', 'value' => $payments['ticketsSold'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-ticket-perforated-fill'],
                        ['label' => 'Total Refunded', 'value' => 'LKR ' . number_format($payments['totalRefunded'], 2), 'color' => 'text-rose-600', 'bg' => 'bg-rose-100', 'icon' => 'bi-arrow-counterclockwise'],
                    ] as $card)
                        <div class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                                    <h3 class="mt-2 text-2xl font-bold text-slate-900">{{ $card['value'] }}</h3>
                                </div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $card['bg'] }}">
                                    <i class="bi {{ $card['icon'] }} text-xl {{ $card['color'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-6 xl:grid-cols-3">
                    <div class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Revenue Trend</h3>
                        <p class="mt-1 text-sm text-slate-500">Completed payment revenue over 6 months</p>
                        <div class="mt-6 h-72">
                            <canvas id="paymentRevenueChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Payment Status</h3>
                        <p class="mt-1 text-sm text-slate-500">Breakdown by transaction status</p>
                        <div class="mt-6 h-72">
                            <canvas id="paymentStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Payment Methods</h3>
                        <p class="mt-1 text-sm text-slate-500">Stripe vs wallet usage</p>
                        <div class="mt-6 h-64">
                            <canvas id="paymentMethodChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Recent Transactions</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $payments['pendingPayments'] }} pending · {{ $payments['pendingRefunds'] }} refund requests
                                </p>
                            </div>
                            <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" section="payments" />
                        </div>
                        <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                            @forelse($payments['recentPayments'] as $payment)
                                <div class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900 truncate">{{ $payment['user'] }}</p>
                                        <p class="text-sm text-slate-500">{{ $payment['reference'] }} · {{ ucfirst($payment['method']) }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="font-bold text-slate-900">LKR {{ number_format($payment['amount'], 2) }}</p>
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            'bg-emerald-100 text-emerald-700' => $payment['status'] === 'completed',
                                            'bg-amber-100 text-amber-700' => $payment['status'] === 'pending',
                                            'bg-rose-100 text-rose-700' => in_array($payment['status'], ['failed', 'cancelled']),
                                        ])>
                                            {{ ucfirst($payment['status']) }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <p class="px-6 py-8 text-center text-slate-500">No payments recorded yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- System Reports --}}
            <div x-show="activeTab === 'system'" x-cloak class="space-y-8">
                <x-report-section-header title="System Reports" description="Platform performance, usage trends, and activity logs">
                    <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" section="system" />
                </x-report-section-header>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach ([
                        ['label' => 'Audit Log Entries', 'value' => $system['totalAuditLogs'], 'color' => 'text-purple-600', 'bg' => 'bg-purple-100', 'icon' => 'bi-journal-text'],
                        ['label' => 'Activity Today', 'value' => $system['auditLogsToday'], 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'icon' => 'bi-lightning'],
                        ['label' => 'This Week', 'value' => $system['auditLogsThisWeek'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-calendar-week'],
                        ['label' => 'Support Tickets', 'value' => $system['totalInquiries'] + $system['totalComplaints'], 'color' => 'text-amber-600', 'bg' => 'bg-amber-100', 'icon' => 'bi-headset'],
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
                    <div class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">System Activity Trend</h3>
                        <p class="mt-1 text-sm text-slate-500">Audit log volume over the last 6 months</p>
                        <div class="mt-6 h-72">
                            <canvas id="systemActivityChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Top Audit Actions</h3>
                        <p class="mt-1 text-sm text-slate-500">Most frequent system actions</p>
                        <div class="mt-6 h-72">
                            <canvas id="systemAuditActionChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 px-6 py-5">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Recent Activity Logs</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $system['totalInquiries'] }} inquiries · {{ $system['totalComplaints'] }} complaints
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-report-export-buttons excel-route="admin.reports.export.excel" pdf-route="admin.reports.export.pdf" section="system" />
                            <a href="{{ route('admin.audit-logs') }}"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-100">
                                View All Logs
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Action</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell">Model</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 hidden md:table-cell">IP</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">When</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($system['recentAuditLogs'] as $log)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900 whitespace-nowrap">{{ $log['user'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-600">{{ $log['action'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-500 hidden sm:table-cell">{{ $log['model'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-500 hidden md:table-cell">{{ $log['ip'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-400 text-right whitespace-nowrap">{{ $log['time'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">No activity logs yet.</td>
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
            window.adminReportData = @json($reports);
        </script>
        @vite('resources/js/admin-reports.js')
    @endpush
</x-app-layout>
