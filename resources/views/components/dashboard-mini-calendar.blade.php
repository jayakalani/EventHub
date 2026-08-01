@props([
    'calendar',
])

@php
    $calendarId = 'mini-cal-'.uniqid();
@endphp

<div
    {{ $attributes->merge(['class' => 'glass-card overflow-hidden']) }}
    x-data="dashboardMiniCalendar(@js($calendar))"
>
    <div class="flex items-start justify-between gap-3 border-b border-white/50 bg-white/30 px-4 py-3.5 backdrop-blur-sm sm:px-5">
        <div class="min-w-0">
            <h2 class="text-base font-bold text-slate-900">{{ $calendar['title'] }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ $calendar['subtitle'] }}</p>
        </div>
        @if (! empty($calendar['calendarUrl']))
            <a href="{{ $calendar['calendarUrl'] }}"
                class="btn-smooth shrink-0 text-xs font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                Full calendar →
            </a>
        @endif
    </div>

    <div class="p-4 sm:p-5">
        <div class="flex items-center justify-between gap-2">
            <button type="button"
                @click="shiftMonth(-1)"
                class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg border border-white/70 bg-white/50 text-slate-600 hover:bg-white/80"
                aria-label="Previous month">
                <i class="bi bi-chevron-left text-sm"></i>
            </button>
            <p class="text-sm font-bold text-slate-900" x-text="monthLabel"></p>
            <button type="button"
                @click="shiftMonth(1)"
                class="btn-smooth flex h-8 w-8 items-center justify-center rounded-lg border border-white/70 bg-white/50 text-slate-600 hover:bg-white/80"
                aria-label="Next month">
                <i class="bi bi-chevron-right text-sm"></i>
            </button>
        </div>

        <div class="mt-3 grid grid-cols-7 gap-1 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400">
            <template x-for="day in weekdays" :key="day">
                <span x-text="day"></span>
            </template>
        </div>

        <div class="mt-1 grid grid-cols-7 gap-1">
            <template x-for="cell in cells" :key="cell.key">
                <button
                    type="button"
                    @click="cell.inMonth && selectDate(cell.date)"
                    :disabled="!cell.inMonth"
                    class="btn-smooth relative flex aspect-square flex-col items-center justify-center rounded-lg text-xs font-semibold transition"
                    :class="cellClasses(cell)"
                >
                    <span x-text="cell.day"></span>
                    <span
                        x-show="cell.count > 0"
                        class="absolute bottom-1 h-1 w-1 rounded-full"
                        :style="'background-color:' + (cell.color || '#6366f1')"
                    ></span>
                </button>
            </template>
        </div>

        <div class="mt-4">
            <div class="mb-2 flex items-center justify-between gap-2">
                <p class="text-xs font-semibold text-slate-700" x-text="selectedLabel"></p>
                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700"
                    x-text="selectedEvents.length + (selectedEvents.length === 1 ? ' event' : ' events')"></span>
            </div>

            <div class="max-h-40 space-y-2 overflow-y-auto pr-0.5">
                <template x-if="selectedEvents.length === 0">
                    <div class="rounded-xl border border-dashed border-white/70 bg-white/40 px-3 py-5 text-center">
                        <p class="text-xs text-slate-500">No events on this day.</p>
                        <a x-show="createUrl" :href="createUrl" class="mt-1 inline-flex text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                            Schedule an event
                        </a>
                    </div>
                </template>

                <template x-for="event in selectedEvents" :key="event.id">
                    <div
                        class="rounded-xl border border-white/60 bg-white/45 px-3 py-2.5 backdrop-blur-sm transition"
                        :class="event.url ? 'cursor-pointer hover:bg-white/75' : ''"
                        :style="'border-left: 3px solid ' + event.color"
                        @click="if (event.url) window.location.href = event.url"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="line-clamp-1 text-sm font-semibold text-slate-900" x-text="event.name"></p>
                            <span class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold text-white"
                                :style="'background-color:' + event.color"
                                x-text="event.statusLabel"></span>
                        </div>
                        <p class="mt-0.5 text-[11px] text-slate-500">
                            <span x-show="event.time" x-text="event.time"></span>
                            <span x-show="event.time && event.place"> · </span>
                            <span x-show="event.place" x-text="event.place"></span>
                        </p>
                        <p x-show="event.organizer" class="mt-0.5 truncate text-[11px] font-medium text-slate-500" x-text="event.organizer"></p>
                    </div>
                </template>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1">
            <template x-for="item in legend" :key="item.key">
                <div class="flex items-center gap-1">
                    <span class="h-1.5 w-1.5 rounded-full" :style="'background-color:' + item.color"></span>
                    <span class="text-[10px] text-slate-500" x-text="item.label"></span>
                </div>
            </template>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            window.dashboardMiniCalendar = function (config) {
                const events = Array.isArray(config.events) ? config.events : [];
                const today = config.today || new Date().toISOString().slice(0, 10);
                const [initYear, initMonth] = String(config.initialMonth || today.slice(0, 7)).split('-').map(Number);

                return {
                    events,
                    today,
                    createUrl: config.createUrl || null,
                    weekdays: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                    year: initYear,
                    month: initMonth,
                    selectedDate: today,
                    legend: Object.entries(config.statusColors || {}).map(([key, color]) => ({
                        key,
                        label: key.charAt(0).toUpperCase() + key.slice(1),
                        color,
                    })),
                    get monthLabel() {
                        return new Date(this.year, this.month - 1, 1).toLocaleString(undefined, {
                            month: 'long',
                            year: 'numeric',
                        });
                    },
                    get selectedLabel() {
                        if (!this.selectedDate) return 'Select a day';
                        const d = new Date(this.selectedDate + 'T12:00:00');
                        if (this.selectedDate === this.today) return 'Today';
                        return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
                    },
                    get selectedEvents() {
                        return this.events.filter((event) => event.date === this.selectedDate);
                    },
                    get cells() {
                        const first = new Date(this.year, this.month - 1, 1);
                        const startPad = first.getDay();
                        const daysInMonth = new Date(this.year, this.month, 0).getDate();
                        const prevDays = new Date(this.year, this.month - 1, 0).getDate();
                        const cells = [];

                        for (let i = 0; i < 42; i++) {
                            let day;
                            let inMonth = true;
                            let date;

                            if (i < startPad) {
                                day = prevDays - startPad + i + 1;
                                inMonth = false;
                                const prev = new Date(this.year, this.month - 2, day);
                                date = this.toKey(prev);
                            } else if (i >= startPad + daysInMonth) {
                                day = i - startPad - daysInMonth + 1;
                                inMonth = false;
                                const next = new Date(this.year, this.month, day);
                                date = this.toKey(next);
                            } else {
                                day = i - startPad + 1;
                                date = this.toKey(new Date(this.year, this.month - 1, day));
                            }

                            const dayEvents = this.events.filter((event) => event.date === date);
                            cells.push({
                                key: date + '-' + i,
                                day,
                                date,
                                inMonth,
                                isToday: date === this.today,
                                isSelected: date === this.selectedDate,
                                count: dayEvents.length,
                                color: dayEvents[0]?.color || null,
                            });
                        }

                        return cells;
                    },
                    toKey(date) {
                        const y = date.getFullYear();
                        const m = String(date.getMonth() + 1).padStart(2, '0');
                        const d = String(date.getDate()).padStart(2, '0');
                        return `${y}-${m}-${d}`;
                    },
                    shiftMonth(delta) {
                        const next = new Date(this.year, this.month - 1 + delta, 1);
                        this.year = next.getFullYear();
                        this.month = next.getMonth() + 1;
                        const stillVisible = this.cells.some((cell) => cell.inMonth && cell.date === this.selectedDate);
                        if (!stillVisible) {
                            const withEvents = this.cells.find((cell) => cell.inMonth && cell.count > 0);
                            this.selectedDate = withEvents?.date
                                || this.toKey(new Date(this.year, this.month - 1, 1));
                        }
                    },
                    selectDate(date) {
                        this.selectedDate = date;
                        const d = new Date(date + 'T12:00:00');
                        this.year = d.getFullYear();
                        this.month = d.getMonth() + 1;
                    },
                    cellClasses(cell) {
                        if (!cell.inMonth) return 'cursor-default text-slate-300 opacity-40';
                        if (cell.isSelected) return 'bg-indigo-600 text-white shadow-sm';
                        if (cell.isToday) return 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200';
                        if (cell.count > 0) return 'bg-white/60 text-slate-800 hover:bg-white/90';
                        return 'text-slate-600 hover:bg-white/50';
                    },
                };
            }
        </script>
    @endpush
@endonce
