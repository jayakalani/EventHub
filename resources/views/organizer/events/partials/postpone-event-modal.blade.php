<div x-show="postponeModal.open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4"
    @keydown.escape.window="postponeModal.open = false">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="postponeModal.open = false"></div>

    <div class="relative my-auto flex max-h-[calc(100vh-2rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
        @click.stop>
        <div class="shrink-0 border-b border-slate-100 px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Postpone Event</p>
                    <h3 class="mt-1 text-xl font-bold text-slate-900">Confirm postponement</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        The event will remain scheduled for the future. Ticket holders can keep their tickets or request a full refund.
                    </p>
                </div>
                <button type="button"
                    @click="postponeModal.open = false"
                    class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <form :action="postponeModal.action" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <input type="hidden" name="_postpone_event_id" :value="postponeModal.eventId || ''">
            <input type="hidden" name="_postpone_event_name" :value="postponeModal.name || ''">
            <input type="hidden" name="_postpone_event_date" :value="postponeModal.date || ''">
            <input type="hidden" name="_postpone_event_time" :value="postponeModal.time || ''">
            <input type="hidden" name="_postpone_event_place" :value="postponeModal.place || ''">

            <div class="flex-1 overflow-y-auto overscroll-contain px-6 py-5">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Event details</p>
                    <p class="mt-2 text-lg font-bold text-slate-900" x-text="postponeModal.name"></p>
                    <p class="mt-2 text-sm text-slate-600">
                        <span x-text="postponeModal.date"></span>
                        <span x-show="postponeModal.time"> • <span x-text="postponeModal.time"></span></span>
                    </p>
                    <p class="mt-1 text-sm text-slate-600" x-text="postponeModal.place"></p>
                </div>

                <div class="mt-5">
                    <label for="postponement_reason" class="block text-sm font-semibold text-slate-700">
                        Reason for postponement <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="postponement_reason"
                        name="postponement_reason"
                        rows="4"
                        required
                        minlength="10"
                        maxlength="2000"
                        placeholder="Please explain why this event is being postponed..."
                        class="mt-2 w-full rounded-2xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">{{ old('postponement_reason') }}</textarea>
                    <p class="mt-2 text-xs text-slate-500">Minimum 10 characters. This reason will be shown to attendees.</p>
                    @error('postponement_reason')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="new_date" class="block text-sm font-semibold text-slate-700">
                            New Event Date
                            <span class="font-normal text-slate-400">(optional)</span>
                        </label>
                        <input id="new_date"
                            type="date"
                            name="new_date"
                            value="{{ old('new_date') }}"
                            min="{{ now()->toDateString() }}"
                            class="mt-2 w-full rounded-2xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        @error('new_date')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="new_time" class="block text-sm font-semibold text-slate-700">
                            New Event Time
                            <span class="font-normal text-slate-400">(optional)</span>
                        </label>
                        <input id="new_time"
                            type="time"
                            name="new_time"
                            value="{{ old('new_time') }}"
                            class="mt-2 w-full rounded-2xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                        @error('new_time')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-500">
                    Leave date blank if the new date is not chosen yet. Ticket holders see “Date Yet To Be Scheduled”; others see the event as upcoming and will be informed when a date is set.
                </p>

                <div class="mt-5 space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label class="flex items-start gap-3 text-sm text-slate-700">
                        <input type="hidden" name="notify_email" value="0">
                        <input type="checkbox"
                            name="notify_email"
                            value="1"
                            class="mt-0.5 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                            @checked((string) old('notify_email', '1') === '1')>
                        <span>
                            <span class="font-semibold">Notify attendees by email</span>
                            <span class="mt-0.5 block text-xs text-slate-500">Send the postponement email to all ticket holders.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 text-sm text-slate-700">
                        <input type="hidden" name="notify_in_app" value="0">
                        <input type="checkbox"
                            name="notify_in_app"
                            value="1"
                            class="mt-0.5 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                            @checked((string) old('notify_in_app', '1') === '1')>
                        <span>
                            <span class="font-semibold">Notify attendees in app</span>
                            <span class="mt-0.5 block text-xs text-slate-500">Show a notification in the attendee notification bell.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="shrink-0 border-t border-slate-100 bg-white px-6 py-4">
                <div class="flex flex-wrap justify-end gap-3">
                    <button type="button"
                        @click="postponeModal.open = false"
                        class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">
                        Confirm Postponement
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
