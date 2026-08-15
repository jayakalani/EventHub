{{-- Performance: snapshot, calendar, ops tables/charts --}}
<div class="space-y-5">
{{-- 2. Platform snapshot --}}
            <section class="space-y-3">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-slate-900">Platform snapshot</h2>
                        <p class="text-xs text-slate-500">{{ $scopeCaption }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($kpis as $kpi)
                        @php
                            $accent = match ($kpi['accent']) {
                                'indigo' => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/70', 'iconText' => 'text-indigo-600'],
                                'blue' => ['top' => 'border-t-blue-500', 'iconBg' => 'bg-blue-100/70', 'iconText' => 'text-blue-600'],
                                'emerald' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/70', 'iconText' => 'text-emerald-600'],
                                default => ['top' => 'border-t-cyan-500', 'iconBg' => 'bg-cyan-100/70', 'iconText' => 'text-cyan-600'],
                            };
                        @endphp
                        <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
                                    <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ $kpi['value'] }}</p>
                                    <p class="mt-1 text-xs font-medium {{ $kpi['subClass'] }}">{{ $kpi['sub'] }}</p>
                                </div>
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['iconBg'] }} backdrop-blur-sm transition-transform duration-300 group-hover:scale-110">
                                    <i class="bi {{ $kpi['icon'] }} text-lg {{ $accent['iconText'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ([
                    ['label' => 'Active', 'value' => $platformAnalytics['active'], 'color' => 'text-cyan-700', 'bg' => 'bg-cyan-50/55', 'border' => 'border-cyan-200/50'],
                    ['label' => 'Upcoming', 'value' => $platformAnalytics['upcoming'], 'color' => 'text-amber-700', 'bg' => 'bg-amber-50/55', 'border' => 'border-amber-200/50'],
                    ['label' => 'Postponed', 'value' => $platformAnalytics['postponed'] ?? 0, 'color' => 'text-orange-700', 'bg' => 'bg-orange-50/55', 'border' => 'border-orange-200/50'],
                    ['label' => 'Completed', 'value' => $platformAnalytics['completed'], 'color' => 'text-emerald-700', 'bg' => 'bg-emerald-50/55', 'border' => 'border-emerald-200/50'],
                    ['label' => 'Cancelled', 'value' => $platformAnalytics['cancelled'], 'color' => 'text-rose-700', 'bg' => 'bg-rose-50/55', 'border' => 'border-rose-200/50'],
                ] as $item)
                    <div class="btn-smooth rounded-xl border {{ $item['border'] }} {{ $item['bg'] }} px-3 py-3 backdrop-blur-md hover:-translate-y-1 hover:bg-white/70 hover:shadow-md sm:px-4">
                        <p class="text-xs font-medium text-slate-500">{{ $item['label'] }} Events</p>
                        <p class="mt-0.5 text-xl font-bold {{ $item['color'] }} sm:text-2xl">{{ number_format($item['value']) }}</p>
                    </div>
                @endforeach
            </div>

            @php
                $conversionFunnel = $conversionFunnel ?? [];
                $funnelViews = (int) (collect($conversionFunnel)->firstWhere('label', 'Views')['count'] ?? 0);
                $funnelPaid = (int) (collect($conversionFunnel)->firstWhere('label', 'Paid')['count'] ?? 0);
                $funnelConversion = $funnelViews > 0 ? round(($funnelPaid / $funnelViews) * 100, 1) : null;
            @endphp
            <div class="glass-card chart-expand-hit group p-4 sm:p-5">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-slate-900">Conversion funnel</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Views → cart → paid</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($funnelConversion !== null)
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                {{ $funnelConversion }}% view → paid
                            </span>
                        @endif
                        <button type="button"
                            @click="openChart('conversionFunnel', @js('Conversion funnel'), @js('Marketplace views to cart to confirmed purchases'))"
                            class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-cyan-50/70 text-cyan-600 backdrop-blur hover:bg-cyan-100/90 hover:shadow-sm"
                            title="View fullscreen"
                            aria-label="View Conversion funnel fullscreen">
                            <i class="bi bi-arrows-fullscreen text-xs"></i>
                        </button>
                    </div>
                </div>
                <button type="button"
                    @click="openChart('conversionFunnel', @js('Conversion funnel'), @js('Marketplace views to cart to confirmed purchases'))"
                    class="btn-smooth block h-48 w-full cursor-pointer rounded-xl text-left hover:bg-white/40 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    aria-label="Open Conversion funnel fullscreen">
                    <canvas id="dashboardConversionFunnelChart" class="pointer-events-none"></canvas>
                </button>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($conversionFunnel as $stage)
                        <span class="rounded-lg border border-white/60 bg-white/50 px-2.5 py-1 text-xs text-slate-500">
                            <span class="font-semibold text-slate-700">{{ $stage['label'] }}:</span>
                            {{ number_format($stage['count']) }}
                            @if ($stage['rate'] !== null)
                                <span class="text-indigo-600">({{ $stage['rate'] }}%)</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
            </section>

            {{-- Needs attention --}}
            @php
                $attentionItems = $attentionQueue['items'] ?? [];
                $attentionIssueCount = (int) ($attentionQueue['count'] ?? 0);
                $attentionRowCount = count($attentionItems);
            @endphp
            <section class="glass-panel overflow-hidden !rounded-2xl {{ $attentionIssueCount > 0 ? 'border-amber-200/60' : 'border-emerald-200/50' }}"
                @if ($attentionRowCount > 3)
                    x-data="{ expanded: false }"
                @endif>
                <div class="flex flex-col gap-3 border-b px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5
                    {{ $attentionIssueCount > 0
                        ? 'border-amber-100/70 bg-gradient-to-r from-amber-50/80 via-orange-50/40 to-rose-50/30'
                        : 'border-emerald-100/70 bg-gradient-to-r from-emerald-50/70 via-cyan-50/40 to-white/40' }}">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl shadow-sm ring-1
                            {{ $attentionIssueCount > 0
                                ? 'bg-amber-100 text-amber-700 ring-amber-200/70'
                                : 'bg-emerald-100 text-emerald-700 ring-emerald-200/70' }}">
                            <i class="bi {{ $attentionIssueCount > 0 ? 'bi-lightning-charge-fill' : 'bi-check2-circle' }}"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-bold text-slate-900">Needs attention</h2>
                                @if ($attentionIssueCount > 0)
                                    <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-amber-600 px-2 text-xs font-bold text-white">
                                        {{ number_format($attentionIssueCount) }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                                Locked accounts, unverified staff, refunds, complaints, and low inventory
                            </p>
                        </div>
                    </div>

                    @if ($attentionRowCount > 3)
                        <button type="button"
                            @click="expanded = !expanded"
                            class="btn-smooth inline-flex items-center justify-center gap-1.5 self-start rounded-xl border border-white/70 bg-white/60 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur-sm hover:bg-white/90 sm:self-auto">
                            <span x-text="expanded ? 'Show less' : 'Show all'"></span>
                            <i class="bi" :class="expanded ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                        </button>
                    @endif
                </div>

                @if ($attentionRowCount === 0)
                    <div class="px-4 py-4 sm:px-5">
                        <p class="text-sm font-medium text-emerald-800">All clear — nothing in the ops queue right now.</p>
                    </div>
                @else
                    <div class="divide-y divide-amber-100/60">
                        @foreach ($attentionItems as $item)
                            @php
                                $accent = match ($item['accent'] ?? 'amber') {
                                    'rose' => [
                                        'icon' => 'bg-rose-100 text-rose-600 ring-rose-200/80',
                                        'badge' => 'bg-rose-100 text-rose-700',
                                        'count' => 'text-rose-700',
                                        'cta' => 'text-rose-700 hover:text-rose-800',
                                    ],
                                    'orange' => [
                                        'icon' => 'bg-orange-100 text-orange-600 ring-orange-200/80',
                                        'badge' => 'bg-orange-100 text-orange-700',
                                        'count' => 'text-orange-700',
                                        'cta' => 'text-orange-700 hover:text-orange-800',
                                    ],
                                    default => [
                                        'icon' => 'bg-amber-100 text-amber-700 ring-amber-200/80',
                                        'badge' => 'bg-amber-100 text-amber-800',
                                        'count' => 'text-amber-700',
                                        'cta' => 'text-amber-700 hover:text-amber-800',
                                    ],
                                };
                            @endphp
                            <a href="{{ $item['href'] }}"
                                @if (! empty($item['section']))
                                    @click.prevent="
                                        setSection(@js($item['section']));
                                        @if (! empty($item['scrollTo']))
                                            $nextTick(() => document.querySelector(@js($item['scrollTo']))?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
                                        @endif
                                    "
                                @endif
                                class="btn-smooth flex items-start gap-3 px-4 py-3.5 hover:bg-amber-50/40 sm:px-5"
                                @if ($loop->index >= 3)
                                    x-show="expanded"
                                    x-cloak
                                    x-transition
                                @endif>
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ring-1 {{ $accent['icon'] }}">
                                    <i class="bi {{ $item['icon'] }} text-sm"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $accent['badge'] }}">
                                            {{ $item['label'] }}
                                        </span>
                                        <p class="text-sm font-bold {{ $accent['count'] }}">{{ number_format($item['count']) }}</p>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $item['message'] }}</p>
                                    <div class="mt-1.5 flex justify-end">
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold {{ $accent['cta'] }}">
                                            {{ $item['cta'] }}
                                            <i class="bi bi-arrow-right text-[10px]"></i>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Low inventory --}}
            @php
                $lowInventoryItems = $lowInventory['items'] ?? [];
                $lowInventoryCount = (int) ($lowInventory['count'] ?? 0);
            @endphp
            @if ($lowInventoryCount > 0)
            <section id="low-inventory" class="glass-card scroll-mt-28 overflow-hidden !p-0">
                <div class="flex flex-col gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-bold text-slate-900">Low inventory</h2>
                            <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-amber-100 px-2 text-xs font-bold text-amber-800">
                                {{ number_format($lowInventoryCount) }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-slate-500">Upcoming and live events near sell-out · live stock, not the date chips</p>
                    </div>
                    <a href="{{ route('admin.events.index') }}"
                        class="btn-smooth whitespace-nowrap text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        Open events list →
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500 backdrop-blur-sm">
                            <tr>
                                <th class="px-4 py-2.5 sm:px-5">Event</th>
                                <th class="px-4 py-2.5 sm:px-5">Organizer</th>
                                <th class="px-4 py-2.5 sm:px-5">Date</th>
                                <th class="px-4 py-2.5 text-right sm:px-5">Left</th>
                                <th class="px-4 py-2.5 text-right sm:px-5">Sold</th>
                                <th class="px-4 py-2.5 sm:px-5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/40">
                            @foreach ($lowInventoryItems as $event)
                                @php
                                    $remaining = (int) ($event['remaining'] ?? 0);
                                    $remainTone = $remaining === 0 ? 'text-rose-700' : 'text-amber-700';
                                @endphp
                                <tr class="btn-smooth relative cursor-pointer hover:bg-white/45">
                                    <td class="px-4 py-3 sm:px-5">
                                        <a href="{{ $event['url'] }}"
                                            class="after:absolute after:inset-0 font-semibold text-slate-900"
                                            aria-label="Open {{ $event['name'] }}">
                                            {{ $event['name'] }}
                                        </a>
                                    </td>
                                    <td class="max-w-[9rem] truncate px-4 py-3 text-slate-600 sm:px-5">{{ $event['organizer'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-700 sm:px-5">{{ $event['when'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $remainTone }} sm:px-5">
                                        {{ number_format($remaining) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-700 sm:px-5">
                                        {{ number_format($event['sold']) }}
                                        <span class="text-slate-400">/ {{ number_format($event['capacity']) }}</span>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            'bg-emerald-100 text-emerald-700' => ($event['status'] ?? '') === 'ongoing',
                                            'bg-sky-100 text-sky-700' => ($event['status'] ?? '') === 'upcoming',
                                        ])>
                                            {{ $event['statusLabel'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($lowInventoryCount > count($lowInventoryItems))
                    <div class="border-t border-white/50 bg-white/25 px-4 py-3 text-center sm:px-5">
                        <a href="{{ route('admin.events.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            View all {{ number_format($lowInventoryCount) }} low-inventory events →
                        </a>
                    </div>
                @endif
            </section>
            @endif

            {{-- This week --}}
            @php
                $upcomingItems = $upcomingThisWeek['items'] ?? [];
                $upcomingCount = (int) ($upcomingThisWeek['count'] ?? 0);
                $upcomingListUrl = $upcomingThisWeek['listUrl'] ?? route('admin.events.index');
            @endphp
            <section class="glass-card overflow-hidden !p-0">
                <div class="flex flex-col gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-bold text-slate-900">This week</h2>
                            @if ($upcomingCount > 0)
                                <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-indigo-100 px-2 text-xs font-bold text-indigo-700">
                                    {{ number_format($upcomingCount) }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-sm text-slate-500">Upcoming and live events on the calendar for the next 7 days · not the date chips</p>
                    </div>
                    <a href="{{ $upcomingListUrl }}"
                        class="btn-smooth whitespace-nowrap text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        Open events list →
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500 backdrop-blur-sm">
                            <tr>
                                <th class="px-4 py-2.5 sm:px-5">Event</th>
                                <th class="px-4 py-2.5 sm:px-5">Organizer</th>
                                <th class="px-4 py-2.5 sm:px-5">Date / time</th>
                                <th class="px-4 py-2.5 text-right sm:px-5">Tickets</th>
                                <th class="px-4 py-2.5 sm:px-5">Status</th>
                                <th class="px-4 py-2.5 text-right sm:px-5">Complaints</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/40">
                            @forelse ($upcomingItems as $event)
                                @php
                                    $statusBadge = match ($event['status'] ?? '') {
                                        'upcoming' => 'bg-sky-100 text-sky-700',
                                        'ongoing' => 'bg-emerald-100 text-emerald-700',
                                        'postponed' => 'bg-amber-100 text-amber-800',
                                        default => 'bg-slate-100 text-slate-600',
                                    };
                                    $capacity = (int) ($event['capacity'] ?? 0);
                                    $sold = (int) ($event['sold'] ?? 0);
                                    $fillPercent = $capacity > 0 ? min(100, (int) round(($sold / $capacity) * 100)) : 0;
                                    $ticketTone = match (true) {
                                        $capacity > 0 && $sold >= $capacity => 'text-rose-700',
                                        $fillPercent >= 85 => 'text-amber-700',
                                        default => 'text-slate-700',
                                    };
                                @endphp
                                <tr class="btn-smooth relative cursor-pointer hover:bg-white/45">
                                    <td class="px-4 py-3 sm:px-5">
                                        <a href="{{ $event['url'] }}"
                                            class="after:absolute after:inset-0"
                                            aria-label="Open {{ $event['name'] }}">
                                            <span class="flex items-center gap-2">
                                                <span class="truncate font-semibold text-slate-900">{{ $event['name'] }}</span>
                                                @if (! empty($event['isToday']))
                                                    <span class="relative shrink-0 rounded-full bg-indigo-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-700">Today</span>
                                                @endif
                                            </span>
                                        </a>
                                    </td>
                                    <td class="max-w-[9rem] truncate px-4 py-3 text-slate-600 sm:px-5">{{ $event['organizer'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-700 sm:px-5">{{ $event['when'] }}</td>
                                    <td class="px-4 py-3 text-right sm:px-5">
                                        <p class="font-semibold {{ $ticketTone }}">
                                            {{ number_format($sold) }}
                                            <span class="font-medium text-slate-400">/ {{ number_format($capacity) }}</span>
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge }}">
                                            {{ $event['statusLabel'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right sm:px-5">
                                        @if ((int) ($event['openComplaints'] ?? 0) > 0)
                                            <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
                                                {{ number_format($event['openComplaints']) }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8">
                                        <x-report-empty-state
                                            class="!min-h-[8rem] border-0 bg-transparent shadow-none"
                                            title="No events in the next 7 days."
                                            hint="Try another organizer, or open the events list for the full catalog."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($upcomingCount > count($upcomingItems))
                    <div class="border-t border-white/50 bg-white/25 px-4 py-3 text-center sm:px-5">
                        <a href="{{ $upcomingListUrl }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            View all {{ number_format($upcomingCount) }} events this week →
                        </a>
                    </div>
                @endif
            </section>

            {{-- 3. Performance + mini calendar --}}
            <div class="grid gap-4 lg:grid-cols-5">
                <section class="glass-card lg:col-span-3 overflow-hidden !p-0">
                    <div class="border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                        <h2 class="text-base font-bold text-slate-900">Organizer Performance</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Top organizers by confirmed sales · {{ $scopeCaption }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500 backdrop-blur-sm">
                                <tr>
                                    <th class="px-4 py-2.5 sm:px-5">Organizer</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Events</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Revenue</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Tickets</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/40">
                                @forelse ($organizerPerformance as $index => $organizer)
                                    <tr class="btn-smooth hover:bg-white/45">
                                        <td class="px-4 py-3 sm:px-5">
                                            <div class="flex items-center gap-2.5">
                                                <span class="flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold backdrop-blur-sm
                                                    {{ $index === 0 ? 'bg-amber-100/80 text-amber-700' : ($index === 1 ? 'bg-slate-200/80 text-slate-700' : ($index === 2 ? 'bg-orange-100/80 text-orange-700' : 'bg-white/60 text-slate-600')) }}">
                                                    {{ $index + 1 }}
                                                </span>
                                                <span class="font-semibold text-slate-900">{{ $organizer['name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-700 sm:px-5">{{ number_format($organizer['events']) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-emerald-700 sm:px-5">{{ $organizer['revenueLabel'] }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700 sm:px-5">{{ number_format($organizer['ticketsSold']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8">
                                            <x-report-empty-state class="!min-h-[8rem] border-0 bg-transparent shadow-none" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <aside class="lg:col-span-2">
                    <x-dashboard-mini-calendar :calendar="$dashboard['miniCalendar']" />
                </aside>
            </div>

            {{-- Top events by revenue --}}
            <section class="grid gap-4 lg:grid-cols-2">
                <div class="glass-card chart-expand-hit group p-4 sm:p-5">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-bold text-slate-900">Top Events</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Highest net revenue listings</p>
                        </div>
                        <button type="button"
                            @click="openChart('topEvents', @js('Top Events by Revenue'), @js('Net ticket revenue after approved refunds'))"
                            class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-emerald-50/70 text-emerald-600 backdrop-blur hover:bg-emerald-100/90 hover:shadow-sm"
                            title="View fullscreen"
                            aria-label="View Top Events fullscreen">
                            <i class="bi bi-arrows-fullscreen text-xs"></i>
                        </button>
                    </div>
                    <button type="button"
                        @click="openChart('topEvents', @js('Top Events by Revenue'), @js('Net ticket revenue after approved refunds'))"
                        class="btn-smooth block h-80 w-full cursor-pointer rounded-xl text-left hover:bg-white/40 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        aria-label="Open Top Events fullscreen">
                        <canvas id="dashboardTopEventsChart" class="pointer-events-none"></canvas>
                    </button>
                </div>

                <section class="glass-card overflow-hidden !p-0">
                    <div class="border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                        <h2 class="text-base font-bold text-slate-900">Revenue leaders</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Tickets, net take, and refund rate</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500 backdrop-blur-sm">
                                <tr>
                                    <th class="px-4 py-2.5 sm:px-5">Event</th>
                                    <th class="px-4 py-2.5 sm:px-5">Organizer</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Tickets</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Net</th>
                                    <th class="px-4 py-2.5 text-right sm:px-5">Refund %</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/40">
                                @forelse ($topEvents as $index => $event)
                                    @php
                                        $refundPercent = (float) ($event['refundPercent'] ?? 0);
                                        $refundTone = match (true) {
                                            $refundPercent >= 15 => 'bg-rose-100 text-rose-700',
                                            $refundPercent >= 5 => 'bg-amber-100 text-amber-800',
                                            default => 'text-slate-500',
                                        };
                                    @endphp
                                    <tr class="btn-smooth relative cursor-pointer hover:bg-white/45">
                                        <td class="px-4 py-3 sm:px-5">
                                            <a href="{{ $event['url'] }}"
                                                class="after:absolute after:inset-0"
                                                aria-label="Open {{ $event['name'] }}">
                                                <span class="flex items-center gap-2.5">
                                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold backdrop-blur-sm
                                                        {{ $index === 0 ? 'bg-amber-100/80 text-amber-700' : ($index === 1 ? 'bg-slate-200/80 text-slate-700' : ($index === 2 ? 'bg-orange-100/80 text-orange-700' : 'bg-white/60 text-slate-600')) }}">
                                                        {{ $index + 1 }}
                                                    </span>
                                                    <span class="truncate font-semibold text-slate-900">{{ $event['name'] }}</span>
                                                </span>
                                            </a>
                                        </td>
                                        <td class="max-w-[8rem] truncate px-4 py-3 text-slate-600 sm:px-5">{{ $event['organizer'] }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700 sm:px-5">{{ number_format($event['tickets']) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-emerald-700 sm:px-5">{{ $event['netLabel'] }}</td>
                                        <td class="px-4 py-3 text-right sm:px-5">
                                            <span @class([
                                                'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                                $refundTone,
                                            ])>
                                                {{ number_format($refundPercent, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8">
                                            <x-report-empty-state
                                                class="!min-h-[8rem] border-0 bg-transparent shadow-none"
                                                title="No ticket revenue yet."
                                                hint="Confirmed sales in this scope will rank here."
                                            />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>

            {{-- Refund rate by organizer --}}
            @php
                $organizerRefundRisk = $organizerRefundRisk ?? [];
            @endphp
            <section class="glass-card overflow-hidden !p-0">
                <div class="border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                    <h2 class="text-base font-bold text-slate-900">Refund rate by organizer</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Who is leaking GMV — not just who is biggest</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500 backdrop-blur-sm">
                            <tr>
                                <th class="px-4 py-2.5 sm:px-5">Organizer</th>
                                <th class="px-4 py-2.5 text-right sm:px-5">GMV</th>
                                <th class="px-4 py-2.5 text-right sm:px-5">Refund %</th>
                                <th class="px-4 py-2.5 text-right sm:px-5">Complaints</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/40">
                            @forelse ($organizerRefundRisk as $organizer)
                                @php
                                    $refundPercent = (float) ($organizer['refundPercent'] ?? 0);
                                    $refundTone = match (true) {
                                        $refundPercent >= 15 => 'bg-rose-100 text-rose-700',
                                        $refundPercent >= 5 => 'bg-amber-100 text-amber-800',
                                        default => 'text-slate-500',
                                    };
                                @endphp
                                <tr class="btn-smooth hover:bg-white/45">
                                    <td class="px-4 py-3 font-semibold text-slate-900 sm:px-5">{{ $organizer['name'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-800 sm:px-5">{{ $organizer['grossLabel'] }}</td>
                                    <td class="px-4 py-3 text-right sm:px-5">
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                            $refundTone,
                                        ])>
                                            {{ number_format($refundPercent, 1) }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right sm:px-5">
                                        @if ((int) ($organizer['openComplaints'] ?? 0) > 0)
                                            <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
                                                {{ number_format($organizer['openComplaints']) }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8">
                                        <x-report-empty-state
                                            class="!min-h-[8rem] border-0 bg-transparent shadow-none"
                                            title="No refund leakage in this scope."
                                            hint="Organizers with approved refunds will rank here by rate."
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- 5. Users + support peek --}}
            <div class="grid gap-4 lg:grid-cols-5">
                <section class="glass-card overflow-hidden !p-0 lg:col-span-3">
                    <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Recent Registrations</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Latest users on the platform</p>
                        </div>
                        <a href="{{ route('admin.users') }}" class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                            View All →
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500 backdrop-blur-sm">
                                <tr>
                                    <th class="px-4 py-2.5 sm:px-5">Name</th>
                                    <th class="px-4 py-2.5 sm:px-5">Role</th>
                                    <th class="px-4 py-2.5 sm:px-5">Registered</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/40">
                                @forelse (collect($users['recent'])->take(6) as $recentUser)
                                    <tr class="btn-smooth hover:bg-white/45">
                                        <td class="px-4 py-3 sm:px-5 font-medium text-slate-900">{{ $recentUser['name'] }}</td>
                                        <td class="px-4 py-3 sm:px-5">
                                            <span class="inline-flex rounded-md border border-white/60 bg-white/50 px-2 py-0.5 text-xs font-semibold text-slate-700 backdrop-blur-sm">
                                                {{ $recentUser['role'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 sm:px-5 text-slate-500">{{ $recentUser['joined'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-10 text-center text-slate-500">No recent registrations.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="glass-card lg:col-span-2 overflow-hidden !p-0">
                    <div class="flex items-center justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
                        <div class="min-w-0">
                            <h2 class="text-base font-bold text-slate-900">Support Overview</h2>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $supportScopeCaption }}</p>
                        </div>
                        <button type="button"
                            @click="setSection('support')"
                            class="btn-smooth text-xs font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                            View all →
                        </button>
                    </div>
                    <div class="space-y-2.5 p-4 sm:p-5">
                        @foreach ([
                            ['label' => 'Open Inquiries', 'value' => $support['openInquiries'], 'icon' => 'bi-chat-left-text', 'color' => 'text-amber-700', 'bg' => 'bg-amber-50/55', 'border' => 'border-amber-200/50', 'iconBg' => 'bg-amber-100/80'],
                            ['label' => 'Open Complaints', 'value' => $support['openComplaints'], 'icon' => 'bi-exclamation-triangle', 'color' => 'text-rose-700', 'bg' => 'bg-rose-50/55', 'border' => 'border-rose-200/50', 'iconBg' => 'bg-rose-100/80'],
                            ['label' => 'Resolved Today', 'value' => $support['resolvedToday'], 'icon' => 'bi-check2-circle', 'color' => 'text-emerald-700', 'bg' => 'bg-emerald-50/55', 'border' => 'border-emerald-200/50', 'iconBg' => 'bg-emerald-100/80'],
                        ] as $item)
                            <div class="btn-smooth flex items-center gap-3 rounded-xl border {{ $item['border'] }} {{ $item['bg'] }} px-3 py-3 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $item['iconBg'] }} backdrop-blur-sm">
                                    <i class="bi {{ $item['icon'] }} {{ $item['color'] }}"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-slate-500">{{ $item['label'] }}</p>
                                    <p class="text-xl font-bold {{ $item['color'] }}">{{ number_format($item['value']) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

</div>
