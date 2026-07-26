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

            @php
                $initialTab = $tab;
                if ($errors->any() && (old('subject') !== null || old('message') !== null)) {
                    $initialTab = 'complaints';
                }
            @endphp

            <div x-data="{ tab: @js($initialTab) }">
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
                <div x-show="tab === 'complaints'" x-cloak class="space-y-4">

                    {{-- Submit Complaint --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm shadow-slate-200/50">
                        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
                                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold tracking-tight text-slate-900 sm:text-lg">
                                        {{ t(['en' => 'Submit a Complaint', 'si' => 'පැමිණිල්ලක් ඉදිරිපත් කරන්න']) }}
                                    </h2>
                                    <p class="mt-0.5 text-sm text-slate-600">
                                        {{ t(['en' => 'Report an issue with your experience. Attach screenshots or PDFs if helpful.', 'si' => 'ඔබේ ප්‍රසංගය පිළිබඳ ගැටලුවක් වාර්තා කරන්න. අවශ්‍ය නම් තිර රූප හෝ PDF අමුණන්න.']) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 sm:p-6">
                            @if($errors->any())
                                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                                    <ul class="list-disc space-y-1 pl-5 text-sm">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form action="{{ route('attendee.complaints.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                                @csrf
                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="lg:col-span-2">
                                        <label for="complaint-subject" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                            {{ t(['en' => 'Subject', 'si' => 'මාතෘකාව']) }}
                                        </label>
                                        <input type="text" id="complaint-subject" name="subject" value="{{ old('subject') }}" required maxlength="255"
                                            placeholder="{{ t(['en' => 'Brief summary of your complaint', 'si' => 'ඔබේ පැමිණිල්ලේ කෙටි සාරාංශය']) }}"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm transition focus:border-primary focus:bg-white focus:ring-primary">
                                    </div>
                                    <div class="lg:col-span-2">
                                        <label for="complaint-message" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                            {{ t(['en' => 'Message', 'si' => 'පණිවිඩය']) }}
                                        </label>
                                        <textarea id="complaint-message" name="message" rows="3" required minlength="10" maxlength="2000"
                                            placeholder="{{ t(['en' => 'Describe your complaint in detail...', 'si' => 'ඔබේ පැමිණිල්ල විස්තරාත්මකව විස්තර කරන්න...']) }}"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm transition focus:border-primary focus:bg-white focus:ring-primary">{{ old('message') }}</textarea>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <label for="complaint-attachments" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                            {{ t(['en' => 'Attachments', 'si' => 'ඇමුණුම්']) }}
                                            <span class="font-normal text-slate-500">{{ t(['en' => '(optional — JPG, PNG, PDF, max 5 MB each, up to 5 files)', 'si' => '(විකල්ප — JPG, PNG, PDF, එකකට උපරිම 5 MB, ගොනු 5ක් දක්වා)']) }}</span>
                                        </label>
                                        <input type="file" id="complaint-attachments" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf"
                                            class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50/50 px-3.5 py-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-primary-dark">
                                    </div>
                                </div>
                                <div class="flex justify-end border-t border-slate-100 pt-4">
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-rose-600/20 transition hover:bg-rose-700">
                                        <i class="bi bi-send" aria-hidden="true"></i>
                                        {{ t(['en' => 'Submit Complaint', 'si' => 'පැමිණිල්ල ඉදිරිපත් කරන්න']) }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>

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
                            <p class="text-sm text-slate-500">{{ t(['en' => 'No complaints yet. Use the form above to submit one.', 'si' => 'තවම පැමිණිලි නැත. ඉදිරිපත් කිරීමට ඉහත පෝරමය භාවිතා කරන්න.']) }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</x-app-layout>
