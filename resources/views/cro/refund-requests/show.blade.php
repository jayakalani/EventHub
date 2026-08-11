@php
    $booking = $refundRequest->ticketBooking;
    $event = $booking?->event;
    $isPending = $refundRequest->isPending();
    $declineTemplates = \App\Support\CroReplyTemplates::forRefundDeclines();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Refund #{{ $refundRequest->id }}</p>
                <h2 class="mt-1 text-3xl font-bold text-slate-900">{{ $event?->name ?? 'Refund Request' }}</h2>
                <p class="mt-1 text-slate-500">
                    Requested {{ $refundRequest->created_at->format('d M Y, H:i') }}
                    by {{ $refundRequest->user->full_name }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @if ($isPending)
                    <span class="inline-flex rounded-full bg-amber-100 px-4 py-1 text-sm font-semibold text-amber-700">
                        Pending Review
                    </span>
                @elseif ($refundRequest->status === \App\Enums\RefundRequestStatusEnum::Approved)
                    <span class="inline-flex rounded-full bg-emerald-100 px-4 py-1 text-sm font-semibold text-emerald-700">
                        Approved
                    </span>
                @else
                    <span class="inline-flex rounded-full bg-red-100 px-4 py-1 text-sm font-semibold text-red-700">
                        Declined
                    </span>
                @endif
                <a href="{{ route('cro.refund-requests.index') }}"
                    class="inline-flex items-center gap-2 rounded-2xl bg-slate-800 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-900 transition">
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
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Refund policy</p>
                                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $refundRequest->refund_percentage }}%</p>
                            </div>
                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Refund amount</p>
                                <p class="mt-1 text-2xl font-bold text-emerald-700">Rs {{ number_format((float) $refundRequest->refund_amount, 2) }}</p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Attendee Reason</p>
                            <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $refundRequest->reason }}</p>
                        </div>

                        @if ($refundRequest->cro_notes || ! $isPending)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                @if ($refundRequest->cro_notes)
                                    <p class="text-xs uppercase tracking-wide text-slate-500">CRO Notes</p>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $refundRequest->cro_notes }}</p>
                                @endif
                                <p class="mt-2 text-xs text-slate-400 {{ $refundRequest->cro_notes ? '' : 'mt-0' }}">
                                    @if ($refundRequest->reviewer)
                                        Reviewed by {{ $refundRequest->reviewer->full_name }}
                                    @elseif (! $isPending)
                                        Processed by System
                                    @endif
                                    @if ($refundRequest->reviewed_at)
                                        · {{ $refundRequest->reviewed_at->format('d M Y, H:i') }}
                                    @endif
                                </p>
                            </div>
                        @endif

                        @if ($isPending)
                            <div class="space-y-4 border-t border-slate-100 pt-5">
                                <form action="{{ route('cro.refund-requests.approve', $refundRequest) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <input type="text" name="cro_notes" placeholder="Optional notes for the attendee..." class="w-full rounded-xl border-slate-300 text-sm">
                                    <button type="submit" class="w-full rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                                        Approve Refund
                                    </button>
                                </form>

                                <form action="{{ route('cro.refund-requests.decline', $refundRequest) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <label for="decline-reason" class="block text-sm font-semibold text-slate-700">
                                        Decline reason <span class="text-red-600">*</span>
                                    </label>
                                    @include('partials.cro-reply-templates', [
                                        'templates' => $declineTemplates,
                                        'textareaId' => 'decline-reason',
                                    ])
                                    <textarea
                                        id="decline-reason"
                                        name="cro_notes"
                                        rows="3"
                                        required
                                        minlength="10"
                                        maxlength="1000"
                                        placeholder="Explain why this refund request is being declined. This will be sent to the attendee."
                                        class="w-full rounded-xl border-slate-300 text-sm"
                                    >{{ old('cro_notes') }}</textarea>
                                    <button type="submit" class="w-full rounded-2xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-700">
                                        Decline Refund
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

                @include('partials.cro-case-context', ['caseContext' => $caseContext])
            </div>
        </div>
    </div>
</x-app-layout>
