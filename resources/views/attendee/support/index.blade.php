<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
            <h2 class="text-lg font-bold leading-tight text-slate-900 sm:text-xl">{{ t(['en' => 'My Support', 'si' => 'මගේ සහාය']) }}</h2>
            <p class="text-xs text-slate-500 sm:text-sm sm:text-right">{{ t(['en' => 'Track your inquiries and complaints.', 'si' => 'ඔබේ විමසුම් සහ පැමිණිලි බලන්න.']) }}</p>
        </div>
    </x-slot>

    <div class="py-5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <div x-data="{ tab: '{{ $tab }}' }">
                <div class="flex gap-1 border-b border-slate-200 mb-4">
                    <button @click="tab = 'inquiries'"
                        :class="tab === 'inquiries' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-3 py-2 text-sm font-semibold border-b-2 transition">
                        {{ t(['en' => 'Inquiries', 'si' => 'විමසුම්']) }} ({{ $inquiries->count() }})
                    </button>
                    <button @click="tab = 'complaints'"
                        :class="tab === 'complaints' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-3 py-2 text-sm font-semibold border-b-2 transition">
                        {{ t(['en' => 'Complaints', 'si' => 'පැමිණිලි']) }} ({{ $complaints->count() }})
                    </button>
                </div>

                {{-- Inquiries --}}
                <div x-show="tab === 'inquiries'" class="space-y-3">
                    @forelse($inquiries as $inquiry)
                        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-slate-900">{{ $inquiry->subject }}</h3>
                                    <p class="text-xs text-slate-500">
                                        {{ t(['en' => 'Event:', 'si' => 'ප්‍රසංග:']) }} {{ $inquiry->event->name }} · {{ $inquiry->created_at->format('d M Y, H:i') }}
                                    </p>
                                </div>
                                @include('partials.support-status-badge', ['status' => $inquiry->status])
                            </div>
                            <div class="px-4 py-3.5">
                                <p class="text-sm text-slate-700 leading-relaxed">{{ $inquiry->message }}</p>

                                @if($inquiry->responses->isNotEmpty())
                                    <div class="mt-3 space-y-2">
                                        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t(['en' => 'CRO Responses', 'si' => 'CRO ප්‍රතිචාර']) }}</h4>
                                        @foreach($inquiry->responses->sortBy('created_at') as $response)
                                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 px-3.5 py-3">
                                                <div class="flex items-center justify-between gap-2 mb-1">
                                                    <p class="text-sm font-semibold text-slate-800">{{ $response->user->full_name }}</p>
                                                    <span class="text-xs text-slate-500">{{ $response->created_at->format('d M Y, H:i') }}</span>
                                                </div>
                                                <p class="text-sm text-slate-700 leading-relaxed">{{ $response->message }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center">
                            <p class="text-sm text-slate-500">{{ t(['en' => 'No inquiries yet. Submit one from an event page.', 'si' => 'තවම විමසුම් නැත. ප්‍රසංග පිටුවකින් එකක් ඉදිරිපත් කරන්න.']) }}</p>
                        </div>
                    @endforelse
                </div>

                {{-- Complaints --}}
                <div x-show="tab === 'complaints'" x-cloak class="space-y-3">
                    @forelse($complaints as $complaint)
                        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-slate-900">{{ $complaint->subject }}</h3>
                                    <p class="text-xs text-slate-500">{{ $complaint->created_at->format('d M Y, H:i') }}</p>
                                </div>
                                @include('partials.support-status-badge', ['status' => $complaint->status])
                            </div>
                            <div class="px-4 py-3.5">
                                <p class="text-sm text-slate-700 leading-relaxed">{{ $complaint->message }}</p>

                                @if($complaint->attachments->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach($complaint->attachments as $attachment)
                                            <a href="{{ route('attendee.complaints.attachments.download', [$complaint, $attachment]) }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                                <i class="bi bi-paperclip"></i>
                                                {{ $attachment->original_filename }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                @if($complaint->responses->isNotEmpty())
                                    <div class="mt-3 space-y-2">
                                        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t(['en' => 'CRO Responses', 'si' => 'CRO ප්‍රතිචාර']) }}</h4>
                                        @foreach($complaint->responses->sortBy('created_at') as $response)
                                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 px-3.5 py-3">
                                                <div class="flex items-center justify-between gap-2 mb-1">
                                                    <p class="text-sm font-semibold text-slate-800">{{ $response->user->full_name }}</p>
                                                    <span class="text-xs text-slate-500">{{ $response->created_at->format('d M Y, H:i') }}</span>
                                                </div>
                                                <p class="text-sm text-slate-700 leading-relaxed">{{ $response->message }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center">
                            <p class="text-sm text-slate-500">{{ t(['en' => 'No complaints yet. Submit one from your dashboard.', 'si' => 'තවම පැමිණිලි නැත. ඔබේ තොරතුරු පිටුවෙන් එකක් ඉදිරිපත් කරන්න.']) }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</x-app-layout>
