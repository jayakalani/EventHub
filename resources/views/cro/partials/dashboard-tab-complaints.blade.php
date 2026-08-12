{{-- Complaint analytics: categories, volume, recent cases --}}
@php
    $complaints = $reports['complaints'];
    $activeInsightFilters = $reports['filters'] ?? ['event' => null, 'from' => null, 'to' => null];
    $selectedEventName = $eventFilter['selectedEventName']
        ?? ($activeInsightFilters['selectedEventName'] ?? null);
@endphp

<div class="space-y-5" id="cro-complaints">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-xl border border-rose-200/60 bg-rose-50/80 text-rose-600 shadow-sm">
                <i class="bi bi-exclamation-triangle text-sm"></i>
            </span>
            <div>
                <h2 class="text-base font-bold tracking-tight text-slate-900">Complaints</h2>
                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                    Categories, volume trends, and recent cases
                    @if ($selectedEventName)
                        · <span class="font-medium text-slate-700">{{ $selectedEventName }}</span>
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route('cro.complaints.index') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
            <i class="bi bi-list-ul"></i>
            Open complaints
        </a>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <section class="glass-card p-4 sm:p-5">
            <h3 class="text-base font-bold text-slate-900">Categories</h3>
            <p class="mt-0.5 text-sm text-slate-500">Payment, tickets, refunds…</p>
            <div class="mt-4 h-64 sm:h-72">
                <canvas id="complaintCategoryPieChart"></canvas>
            </div>
        </section>
        <section class="glass-card p-4 sm:p-5">
            <h3 class="text-base font-bold text-slate-900">Submission trend</h3>
            <p class="mt-0.5 text-sm text-slate-500">Volume in range</p>
            <div class="mt-4 h-64 sm:h-72">
                <canvas id="complaintSubmissionsChart"></canvas>
            </div>
        </section>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="glass-card p-4 sm:p-5">
            <h3 class="text-base font-bold text-slate-900">By type</h3>
            <p class="mt-0.5 text-sm text-slate-500">Broader subject themes</p>
            <div class="mt-4 h-64">
                <canvas id="complaintTypeChart"></canvas>
            </div>
        </section>
        <section class="glass-card p-4 sm:p-5">
            <h3 class="text-base font-bold text-slate-900">Status by type</h3>
            <p class="mt-0.5 text-sm text-slate-500">Handling progress per category</p>
            <div class="mt-4 h-64">
                <canvas id="complaintStatusByTypeChart"></canvas>
            </div>
        </section>
    </div>

    <section class="glass-card overflow-hidden !p-0">
        <div class="flex flex-col gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h3 class="text-base font-bold text-slate-900">Recent complaints</h3>
                <p class="mt-0.5 text-sm text-slate-500">Latest cases in scope</p>
            </div>
            <a href="{{ route('cro.complaints.index') }}"
                class="btn-smooth text-xs font-semibold text-rose-600 hover:text-rose-800">
                View all →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5 sm:px-5">Subject</th>
                        <th class="px-4 py-2.5 sm:px-5 hidden md:table-cell">User</th>
                        <th class="px-4 py-2.5 sm:px-5 hidden lg:table-cell">Event</th>
                        <th class="px-4 py-2.5 sm:px-5 hidden sm:table-cell">Type</th>
                        <th class="px-4 py-2.5 sm:px-5">Status</th>
                        <th class="px-4 py-2.5 text-right sm:px-5">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/40">
                    @forelse($complaints['recentComplaints'] as $complaint)
                        <tr class="btn-smooth hover:bg-white/45 {{ !empty($complaint['href']) ? 'cursor-pointer' : '' }}"
                            @if (!empty($complaint['href']))
                                role="link"
                                tabindex="0"
                                onclick="window.location.href = @js($complaint['href'])"
                                onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href = @js($complaint['href']); }"
                            @endif>
                            <td class="max-w-xs truncate px-4 py-3 font-medium text-slate-900 sm:px-5">
                                @if (!empty($complaint['href']))
                                    <a href="{{ $complaint['href'] }}" class="hover:text-indigo-700 hover:underline" onclick="event.stopPropagation()">
                                        {{ $complaint['subject'] }}
                                    </a>
                                @else
                                    {{ $complaint['subject'] }}
                                @endif
                            </td>
                            <td class="hidden whitespace-nowrap px-4 py-3 text-slate-500 md:table-cell sm:px-5">{{ $complaint['user'] }}</td>
                            <td class="hidden max-w-[10rem] truncate px-4 py-3 text-slate-500 lg:table-cell sm:px-5">{{ $complaint['event'] ?? 'General' }}</td>
                            <td class="hidden px-4 py-3 sm:table-cell sm:px-5">
                                <span class="inline-flex rounded-md bg-slate-100/80 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                    {{ $complaint['type'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 sm:px-5">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $complaint['statusClass'] }}">
                                    {{ $complaint['status'] }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs text-slate-400 sm:px-5">{{ $complaint['submitted'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">No complaints for this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
