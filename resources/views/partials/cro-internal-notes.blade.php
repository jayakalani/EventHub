@php
    $notesRoute = $notesRoute ?? null;
    $notes = $notes ?? null;
@endphp

<section class="overflow-hidden rounded-3xl border border-amber-200 bg-amber-50/40 shadow-sm">
    <div class="border-b border-amber-100 px-5 py-3">
        <h3 class="text-sm font-bold text-amber-950">Internal notes</h3>
        <p class="mt-0.5 text-xs text-amber-800/80">Visible only to CROs — never sent to the attendee.</p>
    </div>
    <form action="{{ $notesRoute }}" method="POST" class="space-y-3 px-5 py-4">
        @csrf
        @method('PATCH')
        <textarea name="internal_notes" rows="4" maxlength="5000"
            placeholder="Add context for other CROs…"
            class="w-full rounded-xl border-amber-200 bg-white text-sm">{{ old('internal_notes', $notes) }}</textarea>
        <button type="submit" class="rounded-xl bg-amber-700 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-800">
            Save notes
        </button>
    </form>
</section>
