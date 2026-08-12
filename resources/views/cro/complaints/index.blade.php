@php
    $filters = $filters ?? ['status' => null, 'q' => null, 'from' => null, 'to' => null];
    $filterQuery = array_filter([
        'status' => $filters['status'] ?? null,
        'q' => $filters['q'] ?? null,
        'from' => $filters['from'] ?? null,
        'to' => $filters['to'] ?? null,
    ], fn ($value) => $value !== null && $value !== '');
    $hasActiveFilters = count($filterQuery) > 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">Complaints</h2>
                <p class="mt-1 text-slate-500">Event-linked complaints for your assigned events, plus general complaints.</p>
            </div>
            <a href="{{ route('cro.dashboard') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-slate-800 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-900">
                Back to Dashboard
            </a>
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

            <div class="rounded-2xl border border-indigo-200/70 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-800">
                Showing complaints for events where you are the assigned CRO, plus general (non-event) complaints.
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($statuses as $s)
                    <a href="{{ route('cro.complaints.index', array_filter(array_merge($filterQuery, ['status' => $s->value]))) }}"
                        class="rounded-3xl border p-5 transition {{ ($filters['status'] ?? '') === $s->value ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                        <p class="text-sm font-medium text-slate-500">{{ $s->label() }}</p>
                        <p class="mt-1 text-3xl font-bold text-slate-900">{{ $counts[$s->value] ?? 0 }}</p>
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('cro.complaints.index') }}"
                class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                            placeholder="Attendee, email, subject…"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select name="status" class="w-full rounded-xl border-slate-300 text-sm">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->value }}" @selected(($filters['status'] ?? null) === $s->value)>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
                            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-xl border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-xl border-slate-300 text-sm">
                        </div>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Apply</button>
                    @if ($hasActiveFilters)
                        <a href="{{ route('cro.complaints.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
                    @endif
                </div>
            </form>

            @if ($complaints->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-16 text-center">
                    <h3 class="text-2xl font-bold text-slate-800">No Complaints</h3>
                    <p class="mt-2 text-slate-500">No complaints match the selected filters for your assigned events.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($complaints as $complaint)
                        <a href="{{ route('cro.complaints.show', $complaint) }}"
                            class="block overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                            <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate text-lg font-bold text-slate-900">{{ $complaint->subject }}</h3>
                                        @include('partials.cro-sla-badges', ['ticket' => $complaint])
                                    </div>
                                    <p class="text-sm text-slate-500">
                                        {{ $complaint->created_at->format('d M Y, H:i') }}
                                        by {{ $complaint->user->full_name }}
                                        · {{ $complaint->event?->name ?? 'General' }}
                                        · {{ $complaint->assignee?->full_name ?? 'Unassigned' }}
                                        @if ($complaint->attachments->isNotEmpty())
                                            · {{ $complaint->attachments->count() }} attachment{{ $complaint->attachments->count() === 1 ? '' : 's' }}
                                        @endif
                                    </p>
                                    <p class="line-clamp-2 text-sm text-slate-600">{{ $complaint->message }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    @include('partials.support-status-badge', ['status' => $complaint->status])
                                    <span class="text-sm font-semibold text-indigo-600">Open →</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                @if ($complaints->hasPages())
                    <div class="pt-2">{{ $complaints->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
