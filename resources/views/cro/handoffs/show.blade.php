@php
    $eventMeta = $handoff['event'] ?? [];
    $checklist = $handoff['checklist'] ?? [];
    $openInquiries = $handoff['openInquiries'] ?? [];
    $pendingRefunds = $handoff['pendingRefunds'] ?? [];
    $suggestedReply = $handoff['suggestedReply'] ?? '';
    $statusLabel = $eventMeta['statusLabel'] ?? ucfirst($handoff['type'] ?? 'updated');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Organizer handoff</p>
                <h2 class="text-3xl font-bold text-slate-900">{{ $eventMeta['name'] ?? $event->name }}</h2>
                <p class="text-slate-500">
                    {{ $statusLabel }}
                    @if (!empty($eventMeta['date']))
                        · {{ $eventMeta['date'] }}
                    @endif
                    @if (!empty($eventMeta['reason']))
                        · {{ $eventMeta['reason'] }}
                    @endif
                </p>
            </div>
            <a href="{{ route('cro.dashboard') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-slate-800 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-900">
                Back to today’s work
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-6">
            <section class="rounded-3xl border border-amber-200 bg-amber-50/60 p-5 sm:p-6">
                <h3 class="text-sm font-bold uppercase tracking-wide text-amber-900">Checklist</h3>
                <p class="mt-1 text-sm text-amber-800/80">Clear open attendee work after this schedule change.</p>
                <ul class="mt-4 space-y-3">
                    @foreach ($checklist as $step)
                        <li class="flex items-start gap-3 rounded-2xl border border-white/80 bg-white/80 px-4 py-3">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $step['done'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                                <i class="bi {{ $step['done'] ? 'bi-check-lg' : 'bi-circle' }} text-sm"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900">{{ $step['label'] }}</p>
                                @if (($step['count'] ?? 0) > 0)
                                    <p class="mt-0.5 text-xs text-slate-500">{{ number_format($step['count']) }} remaining</p>
                                @elseif ($step['done'])
                                    <p class="mt-0.5 text-xs text-emerald-700">Done</p>
                                @endif
                            </div>
                            @if (!empty($step['href']))
                                <a href="{{ $step['href'] }}" class="shrink-0 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Open →</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-base font-bold text-slate-900">Open inquiries</h3>
                        <span class="rounded-lg bg-sky-100 px-2 py-0.5 text-xs font-bold text-sky-800">{{ count($openInquiries) }}</span>
                    </div>
                    <div class="mt-3 divide-y divide-slate-100">
                        @forelse ($openInquiries as $inquiry)
                            <a href="{{ $inquiry['href'] }}" class="flex items-center justify-between gap-3 py-3 hover:text-indigo-700">
                                <p class="truncate text-sm font-medium text-slate-800">{{ $inquiry['subject'] }}</p>
                                <i class="bi bi-chevron-right text-xs text-slate-300"></i>
                            </a>
                        @empty
                            <p class="py-6 text-center text-sm text-slate-500">No open inquiries for this event.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-base font-bold text-slate-900">Pending refunds</h3>
                        <span class="rounded-lg bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">{{ count($pendingRefunds) }}</span>
                    </div>
                    <div class="mt-3 divide-y divide-slate-100">
                        @forelse ($pendingRefunds as $refund)
                            <a href="{{ $refund['href'] }}" class="flex items-center justify-between gap-3 py-3 hover:text-indigo-700">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-800">{{ $refund['attendee'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $refund['amount'] }}</p>
                                </div>
                                <i class="bi bi-chevron-right text-xs text-slate-300"></i>
                            </a>
                        @empty
                            <p class="py-6 text-center text-sm text-slate-500">No pending refunds for this event.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="rounded-3xl border border-indigo-200 bg-indigo-50/50 p-5 sm:p-6"
                x-data="{
                    copied: false,
                    copy() {
                        const text = this.$refs.reply.value;
                        navigator.clipboard.writeText(text).then(() => {
                            this.copied = true;
                            setTimeout(() => this.copied = false, 1800);
                        });
                    }
                }">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-base font-bold text-indigo-950">Suggested attendee reply</h3>
                        <p class="mt-0.5 text-sm text-indigo-800/80">Copy into an inquiry reply for consistent messaging.</p>
                    </div>
                    <button type="button" @click="copy()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        <i class="bi" :class="copied ? 'bi-check-lg' : 'bi-clipboard'"></i>
                        <span x-text="copied ? 'Copied' : 'Copy'"></span>
                    </button>
                </div>
                <textarea x-ref="reply" readonly rows="5"
                    class="mt-4 w-full rounded-2xl border-indigo-200 bg-white text-sm text-slate-700 shadow-sm">{{ $suggestedReply }}</textarea>
            </section>
        </div>
    </div>
</x-app-layout>
