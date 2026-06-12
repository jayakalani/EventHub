@php
    $statuses = \App\Enums\SupportTicketStatusEnum::cases();
@endphp

<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">Inquiries</h2>
                <p class="mt-1 text-slate-500">Review and respond to attendee event inquiries.</p>
            </div>
            <a href="{{ route('cro.dashboard') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-slate-800 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-900 transition">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-6 space-y-8">

            @if(session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-green-800">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($statuses as $s)
                    <a href="{{ route('cro.inquiries.index', ['status' => $s->value]) }}"
                        class="rounded-2xl border p-5 transition {{ ($status ?? '') === $s->value ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                        <p class="text-sm font-medium text-slate-500">{{ $s->label() }}</p>
                        <p class="mt-1 text-3xl font-bold text-slate-900">{{ $counts[$s->value] ?? 0 }}</p>
                    </a>
                @endforeach
            </div>

            <div class="flex gap-2">
                <a href="{{ route('cro.inquiries.index') }}"
                    class="rounded-xl px-4 py-2 text-sm font-semibold {{ empty($status) ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    All
                </a>
                @foreach($statuses as $s)
                    <a href="{{ route('cro.inquiries.index', ['status' => $s->value]) }}"
                        class="rounded-xl px-4 py-2 text-sm font-semibold {{ ($status ?? '') === $s->value ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        {{ $s->label() }}
                    </a>
                @endforeach
            </div>

            @if($inquiries->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-16 text-center">
                    <h3 class="text-2xl font-bold text-slate-800">No Inquiries</h3>
                    <p class="mt-2 text-slate-500">No inquiries match the selected filter.</p>
                </div>
            @else
                <div class="space-y-6">
                    @foreach($inquiries as $inquiry)
                        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50 px-6 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">{{ $inquiry->subject }}</h3>
                                    <p class="text-sm text-slate-500">
                                        {{ $inquiry->created_at->format('d M Y, H:i') }}
                                        by {{ $inquiry->user->full_name }}
                                        · Event: {{ $inquiry->event->name }}
                                    </p>
                                </div>
                                @include('partials.support-status-badge', ['status' => $inquiry->status])
                            </div>

                            <div class="p-6">
                                <div class="rounded-2xl bg-slate-50 p-4 mb-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-500 mb-2">Attendee Message</p>
                                    <p class="text-sm text-slate-700 leading-relaxed">{{ $inquiry->message }}</p>
                                </div>

                                @if($inquiry->responses->isNotEmpty())
                                    <div class="space-y-3 mb-6">
                                        <h4 class="text-sm font-bold text-slate-800">Previous Responses</h4>
                                        @foreach($inquiry->responses->sortBy('created_at') as $response)
                                            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4">
                                                <div class="flex justify-between gap-2 mb-1">
                                                    <p class="text-sm font-semibold text-indigo-800">{{ $response->user->full_name }}</p>
                                                    <span class="text-xs text-slate-500">{{ $response->created_at->format('d M Y, H:i') }}</span>
                                                </div>
                                                <p class="text-sm text-slate-700">{{ $response->message }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <form action="{{ route('cro.inquiries.reply', $inquiry) }}" method="POST" class="space-y-3 mb-4">
                                    @csrf
                                    <label class="block text-sm font-semibold text-slate-700">Reply to attendee</label>
                                    <textarea name="message" rows="3" required minlength="5" maxlength="2000"
                                        placeholder="Type your response..."
                                        class="w-full rounded-xl border-slate-300 text-sm"></textarea>
                                    <button type="submit" class="rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                        Send Reply
                                    </button>
                                </form>

                                <form action="{{ route('cro.inquiries.update-status', $inquiry) }}" method="POST" class="flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">Update Status</label>
                                        <select name="status" class="rounded-xl border-slate-300 text-sm">
                                            @foreach($statuses as $s)
                                                <option value="{{ $s->value }}" @selected($inquiry->status === $s)>{{ $s->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="rounded-2xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">
                                        Update Status
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>

</x-app-layout>
