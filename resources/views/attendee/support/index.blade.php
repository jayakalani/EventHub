<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">{{ t(['en' => 'My Support', 'si' => 'මගේ සහාය']) }}</h2>
                <p class="mt-1 text-slate-500">{{ t(['en' => 'Track your inquiries and complaints.', 'si' => 'ඔබේ විමසුම් සහ පැමිණිලි බලන්න.']) }}</p>
            </div>
            <a href="{{ route('attendee.dashboard') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-slate-800 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-900 transition">
                {{ t(['en' => 'Back to Dashboard', 'si' => 'තොරතුරු පිටුවට ආපසු']) }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div x-data="{ tab: '{{ $tab }}' }">
                <div class="flex gap-2 border-b border-slate-200 mb-6">
                    <button @click="tab = 'inquiries'"
                        :class="tab === 'inquiries' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-3 text-sm font-semibold border-b-2 transition">
                        {{ t(['en' => 'Inquiries', 'si' => 'විමසුම්']) }} ({{ $inquiries->count() }})
                    </button>
                    <button @click="tab = 'complaints'"
                        :class="tab === 'complaints' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-4 py-3 text-sm font-semibold border-b-2 transition">
                        {{ t(['en' => 'Complaints', 'si' => 'පැමිණිලි']) }} ({{ $complaints->count() }})
                    </button>
                </div>

                {{-- Inquiries --}}
                <div x-show="tab === 'inquiries'" class="space-y-4">
                    @forelse($inquiries as $inquiry)
                        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">{{ $inquiry->subject }}</h3>
                                    <p class="text-sm text-slate-500">
                                        {{ t(['en' => 'Event:', 'si' => 'ප්‍රසංග:']) }} {{ $inquiry->event->name }} · {{ $inquiry->created_at->format('d M Y, H:i') }}
                                    </p>
                                </div>
                                @include('partials.support-status-badge', ['status' => $inquiry->status])
                            </div>
                            <div class="p-6">
                                <p class="text-sm text-slate-700 leading-relaxed">{{ $inquiry->message }}</p>

                                @if($inquiry->responses->isNotEmpty())
                                    <div class="mt-6 space-y-3">
                                        <h4 class="text-sm font-bold text-slate-800">{{ t(['en' => 'CRO Responses', 'si' => 'CRO ප්‍රතිචාර']) }}</h4>
                                        @foreach($inquiry->responses->sortBy('created_at') as $response)
                                            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4">
                                                <div class="flex items-center justify-between gap-2 mb-2">
                                                    <p class="text-sm font-semibold text-indigo-800">{{ $response->user->full_name }}</p>
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
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
                            <p class="text-slate-500">{{ t(['en' => 'No inquiries yet. Submit one from an event page.', 'si' => 'තවම විමසුම් නැත. ප්‍රසංග පිටුවකින් එකක් ඉදිරිපත් කරන්න.']) }}</p>
                        </div>
                    @endforelse
                </div>

                {{-- Complaints --}}
                <div x-show="tab === 'complaints'" x-cloak class="space-y-4">
                    @forelse($complaints as $complaint)
                        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                            <div class="border-b border-slate-100 bg-slate-50 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">{{ $complaint->subject }}</h3>
                                    <p class="text-sm text-slate-500">{{ $complaint->created_at->format('d M Y, H:i') }}</p>
                                </div>
                                @include('partials.support-status-badge', ['status' => $complaint->status])
                            </div>
                            <div class="p-6">
                                <p class="text-sm text-slate-700 leading-relaxed">{{ $complaint->message }}</p>

                                @if($complaint->attachments->isNotEmpty())
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        @foreach($complaint->attachments as $attachment)
                                            <a href="{{ route('attendee.complaints.attachments.download', [$complaint, $attachment]) }}"
                                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                                <i class="bi bi-paperclip"></i>
                                                {{ $attachment->original_filename }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                @if($complaint->responses->isNotEmpty())
                                    <div class="mt-6 space-y-3">
                                        <h4 class="text-sm font-bold text-slate-800">CRO Responses</h4>
                                        @foreach($complaint->responses->sortBy('created_at') as $response)
                                            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4">
                                                <div class="flex items-center justify-between gap-2 mb-2">
                                                    <p class="text-sm font-semibold text-indigo-800">{{ $response->user->full_name }}</p>
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
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
                            <p class="text-slate-500">{{ t(['en' => 'No complaints yet. Submit one from your dashboard.', 'si' => 'තවම පැමිණිලි නැත. ඔබේ තොරතුරු පිටුවෙන් එකක් ඉදිරිපත් කරන්න.']) }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</x-app-layout>
