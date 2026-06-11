<div x-show="cancelModal.open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    @keydown.escape.window="cancelModal.open = false">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="cancelModal.open = false"></div>

    <div class="relative w-full max-w-lg rounded-3xl border border-slate-200 bg-white shadow-2xl"
        @click.stop>
        <div class="border-b border-slate-100 px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Cancel Event</p>
                    <h3 class="mt-1 text-xl font-bold text-slate-900">Confirm cancellation</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        This will notify all ticket holders, process full refunds to their wallets, and mark the event as cancelled.
                    </p>
                </div>
                <button type="button"
                    @click="cancelModal.open = false"
                    class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <form :action="cancelModal.action" method="POST" class="px-6 py-5">
            @csrf
            <input type="hidden" name="_cancel_event_id" :value="cancelModal.eventId || ''">
            <input type="hidden" name="_cancel_event_name" :value="cancelModal.name || ''">
            <input type="hidden" name="_cancel_event_date" :value="cancelModal.date || ''">
            <input type="hidden" name="_cancel_event_time" :value="cancelModal.time || ''">
            <input type="hidden" name="_cancel_event_place" :value="cancelModal.place || ''">

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Event details</p>
                <p class="mt-2 text-lg font-bold text-slate-900" x-text="cancelModal.name"></p>
                <p class="mt-2 text-sm text-slate-600">
                    <span x-text="cancelModal.date"></span>
                    <span x-show="cancelModal.time"> • <span x-text="cancelModal.time"></span></span>
                </p>
                <p class="mt-1 text-sm text-slate-600" x-text="cancelModal.place"></p>
            </div>

            <div class="mt-5">
                <label for="cancellation_reason" class="block text-sm font-semibold text-slate-700">
                    Reason for cancellation
                </label>
                <textarea id="cancellation_reason"
                    name="cancellation_reason"
                    rows="4"
                    required
                    minlength="10"
                    maxlength="2000"
                    placeholder="Please explain why this event is being cancelled..."
                    class="mt-2 w-full rounded-2xl border-slate-300 text-sm focus:border-rose-500 focus:ring-rose-500">{{ old('cancellation_reason') }}</textarea>
                <p class="mt-2 text-xs text-slate-500">Minimum 10 characters. This reason will be shown to attendees.</p>
                @error('cancellation_reason')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button"
                    @click="cancelModal.open = false"
                    class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Keep Event
                </button>
                <button type="submit"
                    class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">
                    Confirm Cancel
                </button>
            </div>
        </form>
    </div>
</div>
