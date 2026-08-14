{{-- Attendance: check-in mix, by-event, assigned guest list --}}
@php
    $attendance = $dashboard['attendance'] ?? [];
    $selectedEventName = $eventFilter['selectedEventName'] ?? null;
@endphp

<div class="space-y-5" id="cro-attendance">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-xl border border-teal-200/60 bg-teal-50/80 text-teal-600 shadow-sm">
                <i class="bi bi-person-check text-sm"></i>
            </span>
            <div>
                <h2 class="text-base font-bold tracking-tight text-slate-900">Attendance</h2>
                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                    Check-in health and guest list for your assigned events
                    @if ($selectedEventName)
                        · <span class="font-medium text-slate-700">{{ $selectedEventName }}</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (! empty($attendance['hasOngoingEvents']))
                <a href="{{ $attendance['scanUrl'] }}"
                    class="btn-smooth inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-500/30">
                    <i class="bi bi-qr-code-scan"></i>
                    Scan / Check-in
                </a>
            @endif
            <a href="{{ $attendance['guestListUrl'] }}"
                class="btn-smooth inline-flex items-center gap-2 rounded-xl border border-white/70 bg-white/55 px-3.5 py-2 text-sm font-semibold text-teal-700 shadow-sm backdrop-blur-md transition hover:-translate-y-0.5 hover:border-teal-200 hover:bg-white/85 hover:shadow-md">
                <i class="bi bi-people"></i>
                Full guest list
            </a>
        </div>
    </div>

    <section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ([
            [
                'label' => 'Attendance rate',
                'value' => ($attendance['attendanceRate'] ?? null) !== null
                    ? number_format((float) $attendance['attendanceRate'], 1).'%'
                    : '—',
                'sub' => ((int) ($attendance['eventsFinalized'] ?? 0)) > 0
                    ? number_format((int) $attendance['eventsFinalized']).' completed events'
                    : 'Based on eligible tickets',
                'icon' => 'bi-percent',
                'accent' => 'teal',
            ],
            [
                'label' => 'Checked in',
                'value' => number_format((int) ($attendance['checkedIn'] ?? 0)),
                'sub' => number_format((int) ($attendance['ticketsEligible'] ?? 0)).' eligible tickets',
                'icon' => 'bi-person-check',
                'accent' => 'emerald',
            ],
            [
                'label' => 'No-shows',
                'value' => number_format((int) ($attendance['noShows'] ?? 0)),
                'sub' => 'Completed events only',
                'icon' => 'bi-person-x',
                'accent' => 'rose',
            ],
            [
                'label' => 'Awaiting check-in',
                'value' => number_format((int) ($attendance['awaitingCheckIn'] ?? 0)),
                'sub' => 'Upcoming & ongoing',
                'icon' => 'bi-hourglass-split',
                'accent' => 'amber',
            ],
        ] as $kpi)
            @php
                $accent = match ($kpi['accent']) {
                    'emerald' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/80', 'iconText' => 'text-emerald-600'],
                    'rose' => ['top' => 'border-t-rose-500', 'iconBg' => 'bg-rose-100/80', 'iconText' => 'text-rose-600'],
                    'amber' => ['top' => 'border-t-amber-500', 'iconBg' => 'bg-amber-100/80', 'iconText' => 'text-amber-600'],
                    default => ['top' => 'border-t-teal-500', 'iconBg' => 'bg-teal-100/80', 'iconText' => 'text-teal-600'],
                };
            @endphp
            <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $kpi['label'] }}</p>
                        <p class="mt-1 truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $kpi['value'] }}</p>
                        <p class="mt-1 text-xs font-medium text-slate-500">{{ $kpi['sub'] }}</p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['iconBg'] }} transition duration-300 group-hover:scale-110">
                        <i class="bi {{ $kpi['icon'] }} text-lg {{ $accent['iconText'] }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    @if (! empty($attendance['peakTiming']))
        <p class="rounded-xl border border-teal-200/70 bg-teal-50/70 px-3.5 py-2.5 text-sm text-teal-900">
            <span class="font-semibold">Peak check-in window:</span>
            {{ $attendance['peakTiming']['label'] }}
            <span class="text-teal-700/80">· {{ number_format((int) $attendance['peakTiming']['count']) }} check-ins</span>
        </p>
    @endif

    <div class="grid gap-4 lg:grid-cols-5">
        <section class="glass-card p-4 sm:p-5 lg:col-span-2">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Attendance mix</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Checked in vs no-shows vs awaiting</p>
                </div>
                <button type="button"
                    @click="openChart('attendanceBreakdown', 'Attendance mix', 'Checked in, no-shows on completed events, and tickets still awaiting entry')"
                    class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-teal-50/70 text-teal-600 hover:bg-teal-100/90"
                    title="View fullscreen"
                    aria-label="View attendance mix fullscreen">
                    <i class="bi bi-arrows-fullscreen text-xs"></i>
                </button>
            </div>
            <button type="button"
                @click="openChart('attendanceBreakdown', 'Attendance mix', 'Checked in, no-shows on completed events, and tickets still awaiting entry')"
                class="btn-smooth block h-56 w-full cursor-pointer rounded-xl text-left hover:bg-white/50 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 sm:h-64"
                aria-label="Open attendance mix fullscreen">
                <canvas id="croAttendanceBreakdownChart" class="pointer-events-none"></canvas>
            </button>
        </section>

        <section class="glass-card p-4 sm:p-5 lg:col-span-3">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Check-in timing</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Relative to event start (−2h → +2h)</p>
                </div>
                <button type="button"
                    @click="openChart('checkInTiming', 'Check-in timing', 'When guests check in relative to each event start time')"
                    class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-indigo-50/70 text-indigo-600 hover:bg-indigo-100/90"
                    title="View fullscreen"
                    aria-label="View check-in timing fullscreen">
                    <i class="bi bi-arrows-fullscreen text-xs"></i>
                </button>
            </div>
            <button type="button"
                @click="openChart('checkInTiming', 'Check-in timing', 'When guests check in relative to each event start time')"
                class="btn-smooth block h-56 w-full cursor-pointer rounded-xl text-left hover:bg-white/50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:h-64"
                aria-label="Open check-in timing fullscreen">
                <canvas id="croCheckInTimingChart" class="pointer-events-none"></canvas>
            </button>
        </section>
    </div>

    <section class="glass-card p-4 sm:p-5">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Attendance by event</h3>
                <p class="mt-0.5 text-sm text-slate-500">Checked in stacked against no-shows / awaiting</p>
            </div>
            <button type="button"
                @click="openChart('attendanceByEvent', 'Attendance by event', 'Checked in vs no-shows or awaiting check-in for each assigned event')"
                class="btn-smooth flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/60 bg-emerald-50/70 text-emerald-600 hover:bg-emerald-100/90"
                title="View fullscreen"
                aria-label="View attendance by event fullscreen">
                <i class="bi bi-arrows-fullscreen text-xs"></i>
            </button>
        </div>
        <button type="button"
            @click="openChart('attendanceByEvent', 'Attendance by event', 'Checked in vs no-shows or awaiting check-in for each assigned event')"
            class="btn-smooth block h-72 w-full cursor-pointer rounded-xl text-left hover:bg-white/50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 sm:h-80"
            aria-label="Open attendance by event fullscreen">
            <canvas id="croAttendanceByEventChart" class="pointer-events-none"></canvas>
        </button>
    </section>

    <section class="glass-card overflow-hidden !p-0 hover:!translate-y-0">
        <div class="flex flex-col gap-3 border-b border-white/50 bg-gradient-to-r from-teal-50/70 via-white/30 to-transparent px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h3 class="text-base font-bold tracking-tight text-slate-900">Assigned events</h3>
                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                    No-shows finalize when an event is completed
                    · {{ number_format((int) ($attendance['eventsWithTickets'] ?? 0)) }} events with tickets
                </p>
            </div>
            <div class="relative">
                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                <input type="search" x-model="attendanceQuery" placeholder="Filter events…"
                    class="w-full rounded-xl border-slate-200/80 bg-white/80 py-2 pl-9 pr-3 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:w-52">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-white/35 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 sm:px-5">Event</th>
                        <th class="hidden px-4 py-3 sm:table-cell sm:px-5">Date</th>
                        <th class="px-4 py-3 text-right sm:px-5">Tickets</th>
                        <th class="px-4 py-3 text-right sm:px-5">Checked in</th>
                        <th class="px-4 py-3 text-right sm:px-5">No-shows</th>
                        <th class="hidden px-4 py-3 text-right md:table-cell sm:px-5">Awaiting</th>
                        <th class="px-4 py-3 text-right sm:px-5">Rate</th>
                        <th class="px-4 py-3 text-right sm:px-5">Guest list</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/50">
                    @forelse ($attendance['byEvent'] ?? [] as $row)
                        <tr class="hover:bg-white/50" x-show="matches(@js($row['name']), attendanceQuery)">
                            <td class="px-4 py-3.5 font-semibold text-slate-900 sm:px-5">{{ $row['name'] }}</td>
                            <td class="hidden px-4 py-3.5 text-slate-500 sm:table-cell sm:px-5">{{ $row['date'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-slate-700 sm:px-5">{{ number_format($row['tickets']) }}</td>
                            <td class="px-4 py-3.5 text-right font-semibold tabular-nums text-emerald-600 sm:px-5">{{ number_format($row['checked_in']) }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums sm:px-5">
                                @if ($row['attendance_final'])
                                    <span class="font-semibold text-rose-600">{{ number_format($row['no_shows']) }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="hidden px-4 py-3.5 text-right tabular-nums md:table-cell sm:px-5">
                                @if (! $row['attendance_final'])
                                    <span class="font-semibold text-amber-600">{{ number_format($row['awaiting_check_in']) }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right sm:px-5">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-700' => $row['attendance_rate'] >= 75,
                                    'bg-amber-100 text-amber-700' => $row['attendance_rate'] >= 40 && $row['attendance_rate'] < 75,
                                    'bg-rose-100 text-rose-700' => $row['attendance_rate'] < 40,
                                ])>
                                    {{ number_format($row['attendance_rate'], 1) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right sm:px-5">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <a href="{{ $row['guest_list_url'] }}"
                                        class="inline-flex items-center rounded-lg border border-white/70 bg-white/70 px-2.5 py-1 text-xs font-semibold text-teal-700 hover:bg-teal-50">
                                        Guests
                                    </a>
                                    @if (! empty($row['is_ongoing']))
                                        <a href="{{ $row['scan_url'] }}"
                                            class="inline-flex items-center rounded-lg bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-700">
                                            Scan
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center sm:px-5">
                                <p class="text-sm font-medium text-slate-700">No assigned-event tickets in this range</p>
                                <p class="mt-0.5 text-xs text-slate-500">Guest lists appear here once bookings exist for your events.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
