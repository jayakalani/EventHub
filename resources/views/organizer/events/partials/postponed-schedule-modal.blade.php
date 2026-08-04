<div x-show="scheduleModal.open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4"
    @keydown.escape.window="scheduleModal.open = false">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="scheduleModal.open = false"></div>

    <div class="relative my-auto w-full max-w-lg overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl"
        @click.stop>
        <div class="border-b border-slate-100 px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-600"
                        x-text="scheduleModal.mode === 'upcoming' ? 'Upcoming Event' : 'Postponed Event'"></p>
                    <h3 class="mt-1 text-xl font-bold text-slate-900">Confirm place, date &amp; time</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        <template x-if="scheduleModal.mode === 'upcoming'">
                            <span>Set the confirmed schedule. Status stays <span class="font-semibold text-indigo-700">Upcoming</span>.</span>
                        </template>
                        <template x-if="scheduleModal.mode !== 'upcoming'">
                            <span>Announce the new schedule. Status stays <span class="font-semibold text-amber-700">Postponed</span>.</span>
                        </template>
                    </p>
                </div>
                <button type="button"
                    @click="scheduleModal.open = false"
                    class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <form :action="scheduleModal.action" method="POST" class="px-6 py-5">
            @csrf
            <input type="hidden" name="_schedule_event_id" :value="scheduleModal.eventId || ''">
            <input type="hidden" name="_schedule_event_name" :value="scheduleModal.name || ''">

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Event</p>
                <p class="mt-2 text-lg font-bold text-slate-900" x-text="scheduleModal.name"></p>
            </div>

            <div class="mt-5">
                <label for="schedule_place" class="block text-sm font-semibold text-slate-700">
                    Place <span class="text-rose-500">*</span>
                </label>
                <input id="schedule_place"
                    type="text"
                    name="schedule_place"
                    required
                    value="{{ old('schedule_place') }}"
                    class="mt-2 w-full rounded-2xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                    data-title-case>
                @error('schedule_place')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="schedule_date" class="block text-sm font-semibold text-slate-700">
                        Event Date <span class="text-rose-500">*</span>
                    </label>
                    <input id="schedule_date"
                        type="date"
                        name="schedule_date"
                        required
                        value="{{ old('schedule_date') }}"
                        min="{{ now()->toDateString() }}"
                        class="mt-2 w-full rounded-2xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('schedule_date')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="schedule_time" class="block text-sm font-semibold text-slate-700">
                        Event Time
                        <span class="font-normal text-slate-400">(optional)</span>
                    </label>
                    <input id="schedule_time"
                        type="time"
                        name="schedule_time"
                        value="{{ old('schedule_time') }}"
                        class="mt-2 w-full rounded-2xl border-slate-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('schedule_time')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <label class="mt-5 flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <input type="hidden" name="notify_attendees" value="0">
                <input type="checkbox"
                    name="notify_attendees"
                    value="1"
                    class="mt-0.5 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                    @checked((string) old('notify_attendees', '1') === '1')>
                <span>
                    <span class="font-semibold">Notify interested attendees</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Email and in-app notice about the confirmed schedule.</span>
                </span>
            </label>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button"
                    @click="scheduleModal.open = false"
                    class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                    class="rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">
                    Save Schedule
                </button>
            </div>
        </form>
    </div>
</div>
