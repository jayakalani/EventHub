@php
    $statuses = \App\Enums\SupportTicketStatusEnum::cases();
    $isGeneral = $complaint->isGeneral();
    $canHandle = $isGeneral
        ? ($complaint->isUnassigned() || $complaint->isAssignedTo(auth()->id()))
        : $complaint->isInCroQueue((int) auth()->id());
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Complaint #{{ $complaint->id }}</p>
                <h2 class="text-3xl font-bold text-slate-900">{{ $complaint->subject }}</h2>
                <p class="text-slate-500">
                    {{ $complaint->created_at->format('d M Y, H:i') }}
                    by {{ $complaint->user->full_name }}
                    @if ($complaint->event)
                        · {{ $complaint->event->name }}
                    @else
                        · General complaint
                    @endif
                </p>
                @include('partials.cro-sla-badges', ['ticket' => $complaint])
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @include('partials.support-status-badge', ['status' => $complaint->status])
                <a href="{{ route('cro.complaints.index') }}"
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
                            <p class="text-sm leading-relaxed text-slate-700">{{ $complaint->message }}</p>
                        </div>

                        @if ($complaint->attachments->isNotEmpty())
                            <div>
                                <p class="mb-2 text-sm font-bold text-slate-800">Attachments</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($complaint->attachments as $attachment)
                                        <a href="{{ route('cro.complaints.attachments.download', [$complaint, $attachment]) }}"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                                            <i class="bi bi-paperclip"></i>
                                            {{ $attachment->original_filename }}
                                            <span class="text-slate-400">({{ number_format($attachment->file_size / 1024, 1) }} KB)</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($complaint->responses->isNotEmpty())
                            <div class="space-y-3">
                                <h4 class="text-sm font-bold text-slate-800">Previous Responses</h4>
                                @foreach ($complaint->responses->sortBy('created_at') as $response)
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

                        @if ($canHandle)
                            <form action="{{ route('cro.complaints.reply', $complaint) }}" method="POST" class="space-y-3">
                                @csrf
                                <label class="block text-sm font-semibold text-slate-700">Reply to attendee</label>
                                @include('partials.cro-reply-templates', [
                                    'templates' => $replyTemplates,
                                    'textareaId' => 'complaint-reply-message',
                                ])
                                <textarea id="complaint-reply-message" name="message" rows="4" required minlength="5" maxlength="2000"
                                    placeholder="Type your response..."
                                    class="w-full rounded-xl border-slate-300 text-sm">{{ old('message') }}</textarea>
                                <button type="submit" class="rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Send Reply
                                </button>
                            </form>

                            <form action="{{ route('cro.complaints.update-status', $complaint) }}" method="POST" class="flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-slate-700">Update Status</label>
                                    <select name="status" class="rounded-xl border-slate-300 text-sm">
                                        @foreach ($statuses as $s)
                                            <option value="{{ $s->value }}" @selected($complaint->status === $s)>{{ $s->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="rounded-2xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">
                                    Update Status
                                </button>
                            </form>
                        @else
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                This complaint is claimed by <span class="font-semibold">{{ $complaint->assignee?->full_name ?? 'another CRO' }}</span>.
                                Ask them to reassign it before you reply or change status.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="space-y-4 lg:sticky lg:top-6 lg:self-start">
                    @include('partials.cro-assignment-panel', [
                        'ticket' => $complaint,
                        'croUsers' => $croUsers,
                        'claimRoute' => $isGeneral ? route('cro.complaints.claim', $complaint) : null,
                        'reassignRoute' => $isGeneral ? route('cro.complaints.reassign', $complaint) : null,
                        'allowClaim' => $isGeneral,
                        'allowReassign' => $isGeneral,
                        'assignedViaEvent' => ! $isGeneral,
                        'ownerLabel' => $complaint->queueOwnerName(),
                        'ownerHint' => $isGeneral
                            ? null
                            : 'This complaint belongs to your assigned event. Reply and resolve it directly — no claim needed.',
                    ])
                    @include('partials.cro-internal-notes', [
                        'notes' => $complaint->internal_notes,
                        'notesRoute' => route('cro.complaints.notes', $complaint),
                        'canEdit' => $canHandle,
                    ])
                    @include('partials.cro-case-context', ['caseContext' => $caseContext])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
