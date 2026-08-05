@if(($upcomingThisWeek ?? collect())->isNotEmpty())
    <section class="overflow-hidden rounded-2xl border border-primary/15 bg-white shadow-sm shadow-primary/5">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">
                    {{ t(['en' => 'Reminders', 'si' => 'සිහිකැඳවීම්']) }}
                </p>
                <h2 class="mt-0.5 text-base font-semibold tracking-tight text-slate-900 sm:text-lg">
                    {{ t(['en' => 'Upcoming this week', 'si' => 'මෙම සතියේ ඉදිරි']) }}
                </h2>
                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                    {{ t(['en' => 'Events you have tickets for in the next 7 days.', 'si' => 'ඉදිරි දින 7 තුළ ඔබට ටිකට් ඇති ප්‍රසංග.']) }}
                </p>
            </div>
            <a
                href="{{ route('attendee.bookings.index', ['tab' => 'upcoming']) }}"
                class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-xl bg-primary/10 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/15 sm:self-auto sm:text-sm"
            >
                <i class="bi bi-ticket-perforated" aria-hidden="true"></i>
                {{ t(['en' => 'My Tickets', 'si' => 'මගේ ටිකට්']) }}
            </a>
        </div>

        <div class="flex gap-3 overflow-x-auto px-4 py-3.5 sm:px-5 scrollbar-thin">
            @foreach($upcomingThisWeek as $event)
                @php
                    $startsAt = $event->startsAt();
                    $isToday = $startsAt->isToday();
                    $isTomorrow = $startsAt->isTomorrow();
                    $isSoon = $startsAt->isFuture() && $startsAt->lte(now()->copy()->addHours(3));
                    $isOngoing = $event->status === \App\Models\Event::STATUS_ONGOING
                        || ($isToday && $startsAt->lte(now()) && ! $event->isCompleted() && ! $event->isCancelled());

                    if ($isToday) {
                        $dayLabel = t(['en' => 'Today', 'si' => 'අද']);
                    } elseif ($isTomorrow) {
                        $dayLabel = t(['en' => 'Tomorrow', 'si' => 'හෙට']);
                    } else {
                        $dayLabel = $startsAt->format('D, d M');
                    }
                @endphp

                <a
                    href="{{ route('attendee.events.show', $event) }}"
                    class="group flex w-[240px] shrink-0 flex-col rounded-2xl border border-slate-200 bg-slate-50/80 p-3.5 transition hover:-translate-y-0.5 hover:border-primary/30 hover:bg-white hover:shadow-md hover:shadow-primary/10"
                >
                    <div class="flex items-start justify-between gap-2">
                        <span
                            @class([
                                'rounded-lg px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide',
                                'bg-red-100 text-red-700' => $isSoon || $isOngoing,
                                'bg-amber-100 text-amber-800' => ! $isSoon && ! $isOngoing && ($isToday || $isTomorrow),
                                'bg-primary/10 text-primary' => ! $isSoon && ! $isOngoing && ! $isToday && ! $isTomorrow,
                            ])
                        >
                            {{ $dayLabel }}
                        </span>

                        @if($isOngoing)
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                                {{ t(['en' => 'Now', 'si' => 'දැන්']) }}
                            </span>
                        @elseif($isSoon)
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">
                                {{ t(['en' => 'Soon', 'si' => 'ඉක්මනින්']) }}
                            </span>
                        @endif
                    </div>

                    <p class="mt-2 line-clamp-2 text-sm font-bold text-slate-900 group-hover:text-primary">
                        {{ $event->name }}
                    </p>

                    <p class="mt-1.5 text-xs text-slate-500">
                        <i class="bi bi-clock" aria-hidden="true"></i>
                        {{ $event->time ?: $startsAt->format('H:i') }}
                        ·
                        <i class="bi bi-geo-alt" aria-hidden="true"></i>
                        <span class="line-clamp-1 inline">{{ $event->place }}</span>
                    </p>

                    <div class="mt-auto flex items-center justify-between gap-2 pt-3">
                        <span class="text-[11px] font-medium text-slate-500">
                            {{ (int) $event->user_ticket_count }}
                            {{ t([
                                'en' => ((int) $event->user_ticket_count) === 1 ? 'ticket' : 'tickets',
                                'si' => 'ටිකට්',
                            ]) }}
                        </span>
                        <span class="text-[11px] font-semibold text-primary">
                            {{ t(['en' => 'Open →', 'si' => 'විවෘත කරන්න →']) }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
