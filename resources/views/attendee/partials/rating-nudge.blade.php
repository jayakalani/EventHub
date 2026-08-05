@if(($pendingRatingPrompts ?? collect())->isNotEmpty())
    <section class="overflow-hidden rounded-2xl border border-amber-200/80 bg-amber-50/70 shadow-sm">
        <div class="flex flex-col gap-3 border-b border-amber-200/70 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-amber-700">
                    {{ t(['en' => 'Feedback', 'si' => 'ප්‍රතිචාර']) }}
                </p>
                <h2 class="mt-0.5 text-base font-semibold tracking-tight text-slate-900 sm:text-lg">
                    {{ t(['en' => 'How was your event?', 'si' => 'ඔබේ ප්‍රසංගය කෙසේද?']) }}
                </h2>
                <p class="mt-0.5 text-xs text-amber-900/80 sm:text-sm">
                    {{ t(['en' => 'Rate and comment on events you attended — it only takes a minute.', 'si' => 'ඔබ සහභාගී වූ ප්‍රසංග ශ්‍රේණිගත කර අදහස් දක්වන්න — මිනිත්තුවකින් කළ හැක.']) }}
                </p>
            </div>
        </div>

        <div class="space-y-2 px-4 py-3.5 sm:px-5">
            @foreach($pendingRatingPrompts as $event)
                <div class="flex flex-col gap-3 rounded-xl border border-amber-200/80 bg-white px-3.5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">{{ $event->name }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $event->formattedScheduleDate('d M Y') ?? $event->date }}
                            @if($event->host)
                                · {{ $event->host->name }}
                            @endif
                        </p>
                    </div>
                    <a
                        href="{{ route('attendee.events.show', $event) }}#ratings"
                        class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-amber-500 px-3.5 py-2 text-xs font-semibold text-white hover:bg-amber-600 sm:text-sm"
                    >
                        <i class="bi bi-star-fill" aria-hidden="true"></i>
                        {{ t(['en' => 'Rate & comment', 'si' => 'ශ්‍රේණිගත කරන්න']) }}
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif
