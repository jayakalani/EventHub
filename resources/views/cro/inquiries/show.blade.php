@php
    $statuses = \App\Enums\SupportTicketStatusEnum::cases();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Inquiry #{{ $inquiry->id }}</p>
                <h2 class="text-3xl font-bold text-slate-900">{{ $inquiry->subject }}</h2>
                <p class="text-slate-500">
                    {{ $inquiry->created_at->format('d M Y, H:i') }}
                    by {{ $inquiry->user->full_name }}
                    · Event: {{ $inquiry->event?->name ?? '—' }}
                </p>
                @include('partials.cro-sla-badges', ['ticket' => $inquiry])
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @include('partials.support-status-badge', ['status' => $inquiry->status])
                <a href="{{ route('cro.inquiries.index') }}"
                    class="inline-flex items-center gap-2 rounded-2xl bg-slate-800 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-900">
                    Back to queue
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-6">
            @if (session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-green-800">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="space-y-6 p-6">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="mb-2 text-xs uppercase tracking-wide text-slate-500">Attendee Message</p>
                            <p class="text-sm leading-relaxed text-slate-700">{{ $inquiry->message }}</p>
                        </div>

                        @if ($inquiry->responses->isNotEmpty())
                            <div class="space-y-3">
                                <h4 class="text-sm font-bold text-slate-800">Previous Responses</h4>
                                @foreach ($inquiry->responses->sortBy('created_at') as $response)
                                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4">
                                        <div class="mb-1 flex justify-between gap-2">
                                            <p class="text-sm font-semibold text-indigo-800">{{ $response->user->full_name }}</p>
                                            <span class="text-xs text-slate-500">{{ $response->created_at->format('d M Y, H:i') }}</span>
                                        </div>
                                        <p class="text-sm text-slate-700">{{ $response->message }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @php
                            $canHandle = $inquiry->isUnassigned() || $inquiry->isAssignedTo(auth()->id());
                        @endphp

                        @if ($canHandle)
                            <form action="{{ route('cro.inquiries.reply', $inquiry) }}" method="POST" class="space-y-3">
                                @csrf
                                <label class="block text-sm font-semibold text-slate-700">Reply to attendee</label>
                                @include('partials.cro-reply-templates', [
                                    'templates' => $replyTemplates,
                                    'textareaId' => 'inquiry-reply-message',
                                ])
                                <textarea id="inquiry-reply-message" name="message" rows="4" required minlength="5" maxlength="2000"
                                    placeholder="Type your response..."
                                    class="w-full rounded-xl border-slate-300 text-sm">{{ old('message') }}</textarea>
                                <button type="submit" class="rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Send Reply
                                </button>
                            </form>

                            <form action="{{ route('cro.inquiries.update-status', $inquiry) }}" method="POST" class="flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-slate-700">Update Status</label>
                                    <select name="status" class="rounded-xl border-slate-300 text-sm">
                                        @foreach ($statuses as $s)
                                            <option value="{{ $s->value }}" @selected($inquiry->status === $s)>{{ $s->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="rounded-2xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">
                                    Update Status
                                </button>
                            </form>
                        @else
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                This inquiry is claimed by <span class="font-semibold">{{ $inquiry->assignee?->full_name ?? 'another CRO' }}</span>.
                                Ask them to reassign it before you reply or change status.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="space-y-4 lg:sticky lg:top-6 lg:self-start">
                    @include('partials.cro-assignment-panel', [
                        'ticket' => $inquiry,
                        'croUsers' => $croUsers,
                        'claimRoute' => route('cro.inquiries.claim', $inquiry),
                        'reassignRoute' => route('cro.inquiries.reassign', $inquiry),
                    ])
                    @include('partials.cro-internal-notes', [
                        'notes' => $inquiry->internal_notes,
                        'notesRoute' => route('cro.inquiries.notes', $inquiry),
                        'canEdit' => $inquiry->isUnassigned() || $inquiry->isAssignedTo(auth()->id()),
                    ])
                    @include('partials.cro-case-context', ['caseContext' => $caseContext])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
