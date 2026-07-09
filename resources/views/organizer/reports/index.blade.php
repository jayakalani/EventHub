<x-app-layout>
    <x-slot name="header">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 p-8 shadow-xl">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white"></div>
                <div class="absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-white"></div>
            </div>

            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">Organizer Reports</h1>
                    <p class="mt-2 text-blue-100">
                        Track ticket sales, revenue, attendees, and engagement across your events.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/30 bg-white/10 backdrop-blur-sm px-5 py-3 text-sm font-semibold text-white hover:bg-white/20 transition-all duration-300">
                        <i class="bi bi-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <a href="{{ route('organizer.events.index') }}"
                        class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-indigo-600 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <i class="bi bi-calendar-event"></i>
                        My Events
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $sales = $reports['ticketSales'];
        $revenue = $reports['revenue'];
        $attendees = $reports['attendees'];
        $engagement = $reports['engagement'];
        $validTabs = ['sales', 'revenue', 'attendees', 'engagement'];
        $initialTab = in_array(request('tab'), $validTabs, true) ? request('tab') : 'sales';
    @endphp

    <div class="py-8" x-data="{ activeTab: '{{ $initialTab }}' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Key Visual Insights --}}
            <div class="rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-cyan-50 p-6 shadow-sm">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Key Visual Insights</h2>
                        <p class="mt-1 text-sm text-slate-500">Revenue and ticket sales trends for your events</p>
                    </div>
                    <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="revenue" />
                </div>
                <div class="grid gap-6 lg:grid-cols-2">
                    <x-report-chart-card title="Revenue Trends" description="Monthly earnings from ticket sales" canvas-id="overviewRevenueChart" />
                    <x-report-chart-card title="Ticket Sales Trends" description="Confirmed tickets sold per month" canvas-id="overviewTicketSalesChart" />
                </div>
            </div>

            {{-- Tab Navigation --}}
            <div class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ([
                        'sales' => ['label' => 'Ticket Sales', 'icon' => 'bi-ticket-perforated', 'desc' => 'Sales by event'],
                        'revenue' => ['label' => 'Revenue', 'icon' => 'bi-cash-stack', 'desc' => 'Earnings breakdown'],
                        'attendees' => ['label' => 'Attendees', 'icon' => 'bi-people', 'desc' => 'Guest lists'],
                        'engagement' => ['label' => 'Engagement', 'icon' => 'bi-heart', 'desc' => 'Likes & ratings'],
                    ] as $key => $tab)
                        <button type="button"
                            @click="activeTab = '{{ $key }}'; $nextTick(() => window.dispatchEvent(new CustomEvent('organizer-reports-tab-changed')))"
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

            {{-- Ticket Sales Report --}}
            <div x-show="activeTab === 'sales'" x-cloak class="space-y-8">
                <x-report-section-header title="Ticket Sales Report" description="Sales by event and monthly trends">
                    <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="sales" />
                </x-report-section-header>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach ([
                        ['label' => 'Tickets Sold', 'value' => $sales['totalTicketsSold'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-ticket-perforated-fill'],
                        ['label' => 'My Events', 'value' => $sales['totalEvents'], 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'icon' => 'bi-calendar-event'],
                        ['label' => 'Events with Sales', 'value' => $sales['eventsWithSales'], 'color' => 'text-cyan-600', 'bg' => 'bg-cyan-100', 'icon' => 'bi-graph-up'],
                        ['label' => 'Avg per Event', 'value' => $sales['totalEvents'] > 0 ? round($sales['totalTicketsSold'] / $sales['totalEvents'], 1) : 0, 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100', 'icon' => 'bi-bar-chart'],
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
                        <h3 class="text-lg font-bold text-slate-900">Sales Trend</h3>
                        <p class="mt-1 text-sm text-slate-500">Confirmed ticket sales over the last 6 months</p>
                        <div class="mt-6 h-72">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Top Selling Events</h3>
                        <p class="mt-1 text-sm text-slate-500">Events ranked by tickets sold</p>
                        <div class="mt-6 h-72">
                            <canvas id="salesByEventChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Sales by Event</h3>
                            <p class="mt-1 text-sm text-slate-500">Ticket sales and capacity fill rate per event</p>
                        </div>
                        <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="sales" />
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Event</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Sold</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell">Capacity</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Fill Rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($sales['salesByEvent'] as $event)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $event['name'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-600">{{ $event['sold'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-500 hidden sm:table-cell">{{ $event['capacity'] }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <span @class([
                                                'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                                'bg-emerald-100 text-emerald-700' => $event['fill_rate'] >= 75,
                                                'bg-amber-100 text-amber-700' => $event['fill_rate'] >= 25 && $event['fill_rate'] < 75,
                                                'bg-slate-100 text-slate-600' => $event['fill_rate'] < 25,
                                            ])>
                                                {{ $event['fill_rate'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-slate-500">No events yet. Create an event to start selling tickets.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Revenue Report --}}
            <div x-show="activeTab === 'revenue'" x-cloak class="space-y-8">
                <x-report-section-header title="Revenue Report" description="Total earnings and breakdown per event">
                    <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="revenue" />
                </x-report-section-header>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach ([
                        ['label' => 'Gross Revenue', 'value' => 'LKR ' . number_format($revenue['grossRevenue'], 2), 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100', 'icon' => 'bi-graph-up-arrow'],
                        ['label' => 'Net Revenue', 'value' => 'LKR ' . number_format($revenue['netRevenue'], 2), 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'icon' => 'bi-wallet2'],
                        ['label' => 'Refunded', 'value' => 'LKR ' . number_format($revenue['totalRefunded'], 2), 'color' => 'text-rose-600', 'bg' => 'bg-rose-100', 'icon' => 'bi-arrow-counterclockwise'],
                        ['label' => 'Avg per Event', 'value' => 'LKR ' . number_format($revenue['averagePerEvent'], 2), 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-calculator'],
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
                        <h3 class="text-lg font-bold text-slate-900">Revenue Growth</h3>
                        <p class="mt-1 text-sm text-slate-500">Monthly earnings from confirmed ticket sales</p>
                        <div class="mt-6 h-72">
                            <canvas id="revenueTrendChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Top Revenue Events</h3>
                        <p class="mt-1 text-sm text-slate-500">Highest earning events</p>
                        <div class="mt-6 h-72">
                            <canvas id="revenueByEventChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Revenue Breakdown by Event</h3>
                        <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="revenue" />
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Event</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($revenue['revenueByEvent'] as $event)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $event['name'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-500 hidden sm:table-cell">{{ $event['status'] }}</td>
                                        <td class="px-6 py-4 text-sm font-bold text-emerald-600 text-right">LKR {{ number_format($event['revenue'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-slate-500">No revenue data yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Attendee Report --}}
            <div x-show="activeTab === 'attendees'" x-cloak class="space-y-8">
                <x-report-section-header title="Attendee Report" description="Guest lists with names, emails, and status">
                    <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="attendees" />
                </x-report-section-header>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach ([
                        ['label' => 'Unique Attendees', 'value' => $attendees['totalAttendees'], 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'icon' => 'bi-people-fill'],
                        ['label' => 'Total Bookings', 'value' => $attendees['totalBookings'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-ticket'],
                        ['label' => 'Confirmed', 'value' => $attendees['confirmedBookings'], 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100', 'icon' => 'bi-check-circle'],
                        ['label' => 'Events with Guests', 'value' => collect($attendees['attendeesByEvent'])->where('count', '>', 0)->count(), 'color' => 'text-cyan-600', 'bg' => 'bg-cyan-100', 'icon' => 'bi-calendar-check'],
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
                        <h3 class="text-lg font-bold text-slate-900">Booking Trend</h3>
                        <p class="mt-1 text-sm text-slate-500">New confirmed bookings over the last 6 months</p>
                        <div class="mt-6 h-72">
                            <canvas id="attendeeTrendChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Attendees by Event</h3>
                        <p class="mt-1 text-sm text-slate-500">Top events by guest count</p>
                        <div class="mt-6 h-72">
                            <canvas id="attendeesByEventChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Attendee List</h3>
                            <p class="mt-1 text-sm text-slate-500">Recent bookings with contact details and status</p>
                        </div>
                        <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="attendees" />
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 hidden md:table-cell">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Event</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Booked</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($attendees['recentAttendees'] as $attendee)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900 whitespace-nowrap">{{ $attendee['name'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-500 hidden md:table-cell">{{ $attendee['email'] }}</td>
                                        <td class="px-6 py-4 text-sm text-slate-600">{{ $attendee['event'] }}</td>
                                        <td class="px-6 py-4 hidden sm:table-cell">
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                                {{ $attendee['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-400 text-right whitespace-nowrap">{{ $attendee['booked'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">No attendees yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Engagement Report --}}
            <div x-show="activeTab === 'engagement'" x-cloak class="space-y-8">
                <x-report-section-header title="Engagement Report" description="Likes, comments, ratings, and event popularity">
                    <x-report-export-buttons excel-route="organizer.reports.export.excel" pdf-route="organizer.reports.export.pdf" section="engagement" />
                </x-report-section-header>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach ([
                        ['label' => 'Total Likes', 'value' => $engagement['totalLikes'], 'color' => 'text-rose-600', 'bg' => 'bg-rose-100', 'icon' => 'bi-heart-fill'],
                        ['label' => 'Comments', 'value' => $engagement['totalComments'], 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'bi-chat-dots'],
                        ['label' => 'Ratings', 'value' => $engagement['totalRatings'], 'color' => 'text-amber-600', 'bg' => 'bg-amber-100', 'icon' => 'bi-star-fill'],
                        ['label' => 'Avg Rating', 'value' => $engagement['averageRating'] ? $engagement['averageRating'] . ' / 5' : '—', 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100', 'icon' => 'bi-star-half'],
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
                        <h3 class="text-lg font-bold text-slate-900">Engagement Trend</h3>
                        <p class="mt-1 text-sm text-slate-500">Likes, comments, and ratings over the last 6 months</p>
                        <div class="mt-6 h-72">
                            <canvas id="engagementTrendChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Engagement Mix</h3>
                        <p class="mt-1 text-sm text-slate-500">Distribution of interaction types</p>
                        <div class="mt-6 h-72">
                            <canvas id="engagementBreakdownChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-900">Event Popularity</h3>
                        <p class="mt-1 text-sm text-slate-500">Combined engagement score by event</p>
                        <div class="mt-6 h-64">
                            <canvas id="popularityChart"></canvas>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div class="border-b border-slate-100 px-6 py-5">
                            <h3 class="text-lg font-bold text-slate-900">Engagement by Event</h3>
                            <p class="mt-1 text-sm text-slate-500">Detailed interaction metrics per event</p>
                        </div>
                        <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                            @forelse($engagement['popularityByEvent'] as $event)
                                <div class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900 truncate">{{ $event['name'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            <i class="bi bi-heart text-rose-500"></i> {{ $event['likes'] }}
                                            · <i class="bi bi-chat text-blue-500"></i> {{ $event['comments'] }}
                                            · <i class="bi bi-star text-amber-500"></i> {{ $event['ratings'] }}
                                        </p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        @if($event['avg_rating'])
                                            <p class="text-sm font-bold text-amber-600">{{ $event['avg_rating'] }} ★</p>
                                        @endif
                                        <p class="text-xs text-slate-400">Score: {{ $event['score'] }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="px-6 py-8 text-center text-slate-500">No engagement data yet.</p>
                            @endforelse
                        </div>
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
            window.organizerReportData = @json($reports);
        </script>
        @vite('resources/js/organizer-reports.js')
    @endpush
</x-app-layout>
