@if (($postponementLoginAlerts ?? collect())->isNotEmpty())
    @php
        $hasScheduledPostponement = $postponementLoginAlerts->contains(fn ($event) => ! $event->hasDateYetToBeScheduled());
        $bookingsUrl = route('attendee.bookings.index', ['from_postponement' => 1]);
    @endphp

    <div id="postponement-login-alert"
        class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="postponement-login-alert-title"
        @if ($hasScheduledPostponement)
            data-redirect-url="{{ $bookingsUrl }}"
            data-redirect-after="10000"
        @endif>
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" data-postponement-alert-close></div>

        <div class="relative my-auto flex max-h-[calc(100vh-2rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
            onclick="event.stopPropagation()">
            <div class="shrink-0 border-b border-slate-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">
                            {{ t(['en' => 'Event Postponed', 'si' => 'ප්‍රසංගය කල් දමා ඇත']) }}
                        </p>
                        <h3 id="postponement-login-alert-title" class="mt-1 text-xl font-bold text-slate-900">
                            {{ t(['en' => '⚠ Your event has been postponed', 'si' => '⚠ ඔබේ ප්‍රසංගය කල් දමා ඇත']) }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ t(['en' => 'You hold tickets for the event(s) below. Your tickets remain valid.', 'si' => 'ඔබ පහත ප්‍රසංග(ය) සඳහා ටිකට් දරයි. ඔබේ ටිකට් වලංගුව පවතී.']) }}
                        </p>
                    </div>
                    <button type="button"
                        data-postponement-alert-close
                        class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                        aria-label="{{ t(['en' => 'Close', 'si' => 'වසන්න']) }}">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="flex-1 space-y-3 overflow-y-auto overscroll-contain px-6 py-5">
                @foreach ($postponementLoginAlerts as $event)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-bold text-amber-950">{{ $event->name }}</p>

                        @if ($event->hasDateYetToBeScheduled())
                            <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-amber-700">
                                {{ t(['en' => 'Date Yet To Be Scheduled', 'si' => 'දිනය තවම නියම වී නැත']) }}
                            </p>
                            @if ($event->postponement_reason)
                                <p class="mt-2 text-sm text-amber-800">{{ $event->postponement_reason }}</p>
                            @endif
                            <a href="{{ route('attendee.events.show', $event) }}"
                                class="mt-3 inline-flex text-sm font-semibold text-amber-800 underline hover:text-amber-950">
                                {{ t(['en' => 'View event', 'si' => 'ප්‍රසංගය බලන්න']) }}
                            </a>
                        @else
                            <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-amber-700">
                                {{ t(['en' => 'New Date', 'si' => 'නව දිනය']) }}
                            </p>
                            <p class="mt-1 text-sm font-semibold text-amber-900">
                                {{ $event->formattedScheduleDate('d M Y') ?? $event->date }}
                                @if ($event->time)
                                    • {{ $event->time }}
                                @endif
                            </p>
                            @if ($event->postponement_reason)
                                <p class="mt-2 text-sm text-amber-800">{{ $event->postponement_reason }}</p>
                            @endif
                            <p class="mt-3 text-sm font-semibold text-amber-950">
                                {{ t(['en' => 'Can you attend on this date?', 'si' => 'ඔබට මෙම දිනයේ සහභාගී විය හැකිද?']) }}
                            </p>
                            <p class="mt-1 text-xs leading-relaxed text-amber-800">
                                {{ t(['en' => 'You will be taken to My Tickets shortly. If you can attend, keep your ticket. If not, you may request a full refund.', 'si' => 'ඔබ ඉක්මනින් මගේ ටිකට් වෙත යවනු ලැබේ. සහභාගී විය හැකි නම් ටිකට් තබා ගන්න. නැතිනම් සම්පූර්ණ ආපසු ගෙවීමක් ඉල්ලිය හැක.']) }}
                            </p>
                        @endif
                    </div>
                @endforeach

                @if ($hasScheduledPostponement)
                    <p class="text-center text-xs text-slate-500" data-postponement-redirect-countdown>
                        {{ t(['en' => 'Redirecting to My Tickets…', 'si' => 'මගේ ටිකට් වෙත යොමු කරමින්…']) }}
                    </p>
                @endif
            </div>

            <div class="shrink-0 border-t border-slate-100 bg-white px-6 py-4">
                <form method="POST"
                    action="{{ route('attendee.postponement-alerts.dismiss') }}"
                    class="flex flex-wrap justify-end gap-3">
                    @csrf
                    @foreach ($postponementLoginAlerts as $event)
                        <input type="hidden" name="event_ids[]" value="{{ $event->id }}">
                    @endforeach
                    <button type="button"
                        data-postponement-alert-close
                        class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        {{ t(['en' => 'Close', 'si' => 'වසන්න']) }}
                    </button>
                    @if ($hasScheduledPostponement)
                        <a href="{{ $bookingsUrl }}"
                            data-postponement-alert-go-tickets
                            class="rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">
                            {{ t(['en' => 'Go to My Tickets', 'si' => 'මගේ ටිකට් වෙත යන්න']) }}
                        </a>
                    @else
                        <button type="submit"
                            class="rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">
                            {{ t(['en' => 'Dismiss permanently', 'si' => 'ස්ථිරව ඉවත් කරන්න']) }}
                        </button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const root = document.getElementById('postponement-login-alert');
            if (!root) return;

            let redirected = false;
            let timer = null;

            const clearTimer = () => {
                if (timer) {
                    clearTimeout(timer);
                    timer = null;
                }
            };

            const close = () => {
                clearTimer();
                root.remove();
            };

            const goTickets = () => {
                if (redirected) return;
                const url = root.dataset.redirectUrl;
                if (!url) return;
                redirected = true;
                clearTimer();
                window.location.href = url;
            };

            root.querySelectorAll('[data-postponement-alert-close]').forEach((el) => {
                el.addEventListener('click', close);
            });

            root.querySelectorAll('[data-postponement-alert-go-tickets]').forEach((el) => {
                el.addEventListener('click', () => {
                    redirected = true;
                    clearTimer();
                });
            });

            document.addEventListener('keydown', function onEsc(e) {
                if (e.key === 'Escape') {
                    close();
                    document.removeEventListener('keydown', onEsc);
                }
            });

            const delay = Number(root.dataset.redirectAfter || 0);
            if (root.dataset.redirectUrl && delay > 0) {
                timer = setTimeout(goTickets, delay);
            }
        })();
    </script>
@endif
