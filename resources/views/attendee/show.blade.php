<x-app-layout>

    @php
        $eventStatus = $event->status ?? 'upcoming';
        $isCancelled = $event->isCancelled();
        $isCompleted = $event->isCompleted();
        $statusStyles = [
            'upcoming' => 'bg-blue-500/90 text-white border-blue-400/30',
            'ongoing' => 'bg-emerald-500 text-white border-emerald-400/30',
            'completed' => 'bg-slate-500 text-white border-slate-400/30',
            'cancelled' => 'bg-rose-500 text-white border-rose-400/30',
        ];
        $statusClass = $statusStyles[$eventStatus] ?? $statusStyles['upcoming'];
        $formattedDate = \Carbon\Carbon::parse($event->date)->format('D, M j, Y');
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
            <div class="flex min-w-0 items-center gap-x-2.5">
                <a href="{{ route('attendee.dashboard') }}"
                    class="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-800 sm:text-sm">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ t(['en' => 'Back', 'si' => 'ආපසු']) }}
                </a>
                <span class="hidden h-3.5 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
                <p class="min-w-0 truncate text-xs text-slate-500 sm:text-sm">
                    <span class="font-medium text-slate-700">{{ Str::limit($event->name, 50) }}</span>
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                @unless ($isCancelled || $isCompleted)
                <form action="{{ route('attendee.events.like', $event) }}" method="POST">
                    @csrf
                    <button type="submit"
                        aria-label="{{ $isLiked ? t(['en' => 'Unlike event', 'si' => 'කැමති ඉවත් කරන්න']) : t(['en' => 'Like event', 'si' => 'කැමති වන්න']) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-semibold transition sm:text-sm
                        {{ $isLiked ? 'border-[#1877F2] bg-[#1877F2] text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300' }}">
                        <i class="bi {{ $isLiked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}" aria-hidden="true"></i>
                        {{ number_format($likesCount) }}
                    </button>
                </form>

                <form action="{{ route('attendee.events.save', $event) }}" method="POST">
                    @csrf
                    <button type="submit"
                        aria-label="{{ $isSaved ? t(['en' => 'Unsave event', 'si' => 'සුරැකීම ඉවත් කරන්න']) : t(['en' => 'Save event', 'si' => 'ප්‍රසංගය සුරකින්න']) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-semibold transition sm:text-sm
                        {{ $isSaved ? 'border-amber-500 bg-amber-500 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300' }}">
                        <i class="bi {{ $isSaved ? 'bi-bookmark-fill' : 'bi-bookmark' }}" aria-hidden="true"></i>
                        {{ $isSaved ? t(['en' => 'Saved', 'si' => 'සුරකින ලදී']) : t(['en' => 'Save', 'si' => 'සුරකින්න']) }}
                    </button>
                </form>
                @endunless
            </div>
        </div>
    </x-slot>

    <div class="py-5">
        <div x-data="{ showModal: false, selected: { id: null, name: '', price: 0, available: 0, color: '' }, qty: 1, amount: 0 }">
            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

                @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                        <div class="flex gap-3">
                            <i class="bi bi-exclamation-triangle-fill shrink-0 text-red-500 mt-0.5" aria-hidden="true"></i>
                            <div class="text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                        class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill shrink-0 text-emerald-500" aria-hidden="true"></i>
                                <p class="text-sm text-emerald-700">{{ session('success') }}</p>
                            </div>
                            <button @click="show=false" class="text-emerald-500 hover:text-emerald-700" aria-label="{{ t(['en' => 'Dismiss', 'si' => 'ඉවතලන්න']) }}">
                                <i class="bi bi-x-lg text-sm" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                @endif

                @if ($isCancelled)
                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                        <div class="flex gap-3">
                            <i class="bi bi-x-circle-fill shrink-0 text-rose-500 mt-0.5" aria-hidden="true"></i>
                            <div class="text-sm">
                                <p class="font-semibold text-rose-900">{{ t(['en' => 'Event Cancelled', 'si' => 'ප්‍රසංගය අවලංගුයි']) }}</p>
                                @if ($event->cancellation_reason)
                                    <p class="mt-1 text-rose-800">{{ $event->cancellation_reason }}</p>
                                @endif
                                <p class="mt-1 text-rose-700">
                                    {{ t(['en' => 'This event is no longer available for booking or interaction. If you purchased tickets, refunds have been processed to your wallet.', 'si' => 'මෙම ප්‍රසංගය තවදුරටත් වෙන්කිරීම හෝ අන්තර්ක්‍රියා සඳහා ලබා ගත නොහැක. ඔබ ටිකට් මිලදී ගෙන තිබේ නම්, ආපසු ගෙවීම් ඔබේ මුදල් පසුම්බියට ලැබී ඇත.']) }}
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif ($isCompleted)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex gap-3">
                            <i class="bi bi-check-circle-fill shrink-0 text-slate-500 mt-0.5" aria-hidden="true"></i>
                            <div class="text-sm">
                                <p class="font-semibold text-slate-900">{{ t(['en' => 'Event Completed', 'si' => 'ප්‍රසංගය අවසන්']) }}</p>
                                <p class="mt-1 text-slate-700">
                                    {{ t(['en' => 'This event has ended. You can still review event details, your ticket history, and any comments, likes, or ratings you submitted.', 'si' => 'මෙම ප්‍රසංගය අවසන් වී ඇත. ඔබට තවමත් ප්‍රසංගය් විස්තර, ටිකට් ඉතිහාසය සහ ඔබ ඉදිරිපත් කළ අදහස්, කැමති හෝ ශ්‍රේණිගත කිරීම් සමාලෝචනය කළ හැක.']) }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Hero --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="grid sm:grid-cols-[0.85fr_1.15fr]">

                        {{-- Image panel --}}
                        <div class="relative overflow-hidden bg-slate-900">
                            <div class="aspect-[4/3] sm:aspect-auto sm:h-full sm:min-h-[240px]">
                                @if ($event->cover)
                                    <img
                                        src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                        alt=""
                                        aria-hidden="true"
                                        class="absolute inset-0 h-full w-full scale-110 object-cover blur-xl brightness-[0.6]"
                                    >
                                    <div class="relative z-[1] flex h-full items-center justify-center p-4 sm:p-5">
                                        <img
                                            src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                            alt="{{ $event->name }}"
                                            class="h-full w-auto max-w-full rounded-2xl object-contain shadow-2xl ring-1 ring-white/15 transition duration-300 hover:scale-[1.03]"
                                        >
                                    </div>
                                @else
                                    <div class="flex h-full items-center justify-center bg-gradient-to-br from-[#0F0363] to-[#2A1585] text-sm font-medium text-violet-200/80">
                                        {{ t(['en' => 'No Image', 'si' => 'රූපයක් නැත']) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Details panel --}}
                        <div class="flex flex-col justify-between gap-4 p-5 sm:p-6">

                            {{-- Top: badges + title + meta --}}
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-indigo-600">
                                        {{ $event->eventCategory->name ?? t(['en' => 'Event', 'si' => 'ප්‍රසංගය']) }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $statusClass }}">
                                        {{ $isCancelled ? t(['en' => 'Cancelled', 'si' => 'අවලංගුයි']) : ($isCompleted ? t(['en' => 'Completed', 'si' => 'අවසන්']) : ucfirst($eventStatus)) }}
                                    </span>
                                </div>

                                <h1 class="text-lg font-bold leading-snug text-slate-900 sm:text-xl lg:text-2xl">
                                    {{ $event->name }}
                                </h1>

                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i class="bi bi-geo-alt text-slate-400" aria-hidden="true"></i>
                                        {{ $event->place }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <i class="bi bi-calendar3 text-slate-400" aria-hidden="true"></i>
                                        {{ $formattedDate }}
                                    </span>
                                    @if ($event->time)
                                        <span class="inline-flex items-center gap-1.5">
                                            <i class="bi bi-clock text-slate-400" aria-hidden="true"></i>
                                            {{ $event->time }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Bottom: compact stats with icons --}}
                            <div class="grid grid-cols-2 gap-2.5 rounded-xl border border-slate-100 bg-slate-50/70 px-3 py-2.5 sm:grid-cols-3 lg:grid-cols-5">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                        <i class="bi bi-person-badge text-sm" aria-hidden="true"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ t(['en' => 'Host', 'si' => 'සත්කාරක']) }}</p>
                                        @if($event->host)
                                            <a href="{{ route('attendee.hosts.show', $event->host) }}"
                                                title="{{ $event->host->name }}"
                                                class="block truncate text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline transition">
                                                {{ $event->host->name }}
                                            </a>
                                        @else
                                            <p class="truncate text-xs font-bold text-slate-800">—</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                        <i class="bi bi-ticket-perforated text-sm" aria-hidden="true"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ t(['en' => 'Capacity', 'si' => 'ධාරිතාව']) }}</p>
                                        <p class="text-xs font-bold text-slate-800">{{ number_format($event->total_tickets) }} {{ t(['en' => 'tickets', 'si' => 'ටිකට්']) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                                        <i class="bi bi-headset text-sm" aria-hidden="true"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ t(['en' => 'Contact', 'si' => 'සම්බන්ධ']) }}</p>
                                        <p class="truncate text-xs font-bold text-slate-800">{{ $event->contactPerson->full_name ?? '—' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-500">
                                        <i class="bi bi-star-fill text-sm" aria-hidden="true"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ t(['en' => 'Rating', 'si' => 'ශ්‍රේණිය']) }}</p>
                                        <p class="text-xs font-bold text-slate-800">
                                            @if ($ratingsCount > 0)
                                                {{ number_format($averageRating, 1) }}/5
                                                <span class="font-medium text-slate-500">· {{ $ratingsCount }}</span>
                                            @else
                                                {{ t(['en' => 'No ratings yet', 'si' => 'තවම නැත']) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#1877F2]/10 text-[#1877F2]">
                                        <i class="bi bi-hand-thumbs-up-fill text-sm" aria-hidden="true"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ t(['en' => 'Likes', 'si' => 'කැමති']) }}</p>
                                        <p class="text-xs font-bold text-[#1877F2]">{{ number_format($likesCount) }}</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="grid gap-8 xl:grid-cols-3">
                    {{-- Main content --}}
                    <div class="space-y-8 xl:col-span-2">

                        {{-- About --}}
                        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                            <div class="mb-5 flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-slate-900">{{ t(['en' => 'About This Event', 'si' => 'මෙම ප්‍රසංගය පිළිබඳ']) }}</h2>
                                    <p class="text-sm text-slate-500">{{ t(['en' => 'Everything you need to know before you book.', 'si' => 'වෙන්කරවා ගැනීමට පෙර ඔබ දැනගත යුතු සියල්ල.']) }}</p>
                                </div>
                            </div>
                            <div class="prose prose-slate max-w-none text-slate-600">
                                <p class="leading-relaxed whitespace-pre-line">{{ $event->description ?: t(['en' => 'No description provided for this event yet.', 'si' => 'මෙම ප්‍රසංගය සඳහා තවම විස්තරයක් ලබා දී නැත.']) }}</p>
                            </div>
                        </section>

                        {{-- Ratings --}}
                        @unless ($isCancelled)
                        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8" x-data="{ hover: 0, selected: {{ $userRating ?? 0 }} }">
                            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                                        <i class="bi bi-star" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-slate-900">{{ t(['en' => 'Ratings', 'si' => 'ශ්‍රේණිගත කිරීම්']) }}</h2>
                                        <p class="text-sm text-slate-500">{{ t(['en' => 'Rate this event and see what others think.', 'si' => 'මෙම ප්‍රසංගය ශ්‍රේණිගත කර අනෙක් අය සිතන දේ බලන්න.']) }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex w-fit items-center rounded-full bg-amber-50 px-4 py-1.5 text-sm font-semibold text-amber-700">
                                    @if ($ratingsCount > 0)
                                        {{ number_format($averageRating, 1) }} ★ · {{ $ratingsCount }} {{ t(['en' => 'total', 'si' => 'මුළු']) }}
                                    @else
                                        {{ t(['en' => 'No ratings yet', 'si' => 'තවම ශ්‍රේණිගත කිරීම් නැත']) }}
                                    @endif
                                </span>
                            </div>

                            <form action="{{ route('attendee.events.ratings.store', $event) }}" method="POST"
                                class="mb-6 rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                                @csrf
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ $userRating ? t(['en' => 'Update your rating', 'si' => 'ඔබේ ශ්‍රේණිගත කිරීම යාවත්කාලීන කරන්න']) : t(['en' => 'Rate this event', 'si' => 'මෙම ප්‍රසංගය ශ්‍රේණිගත කරන්න']) }}
                                </p>
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    <div class="flex items-center gap-1">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <button type="button"
                                                @click="selected = {{ $star }}"
                                                @mouseenter="hover = {{ $star }}"
                                                @mouseleave="hover = 0"
                                                class="rounded-lg p-1 transition hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400">
                                                <svg class="h-8 w-8"
                                                    :class="(hover || selected) >= {{ $star }} ? 'text-amber-400' : 'text-slate-300'"
                                                    fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                                </svg>
                                            </button>
                                        @endfor
                                    </div>
                                    <input type="hidden" name="score" :value="selected" required>
                                    <button type="submit" :disabled="selected < 1"
                                        class="inline-flex items-center rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50">
                                        {{ $userRating ? t(['en' => 'Update Rating', 'si' => 'ශ්‍රේණිගත කිරීම යාවත්කාලීන කරන්න']) : t(['en' => 'Submit Rating', 'si' => 'ශ්‍රේණිගත කිරීම ඉදිරිපත් කරන්න']) }}
                                    </button>
                                </div>
                                @error('score')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @if ($userRating)
                                    <p class="mt-2 text-sm text-slate-500">{{ t(['en' => 'Your current rating:', 'si' => 'ඔබේ වත්මන් ශ්‍රේණිගත කිරීම:']) }} {{ $userRating }}/5</p>
                                @endif
                            </form>

                            @if ($userRating)
                                <form action="{{ route('attendee.events.ratings.destroy', $event) }}" method="POST"
                                    class="mb-6 -mt-2"
                                    onsubmit="return confirm(@js(t(['en' => 'Remove your rating?', 'si' => 'ඔබේ ශ්‍රේණිගත කිරීම ඉවත් කරන්නද?'])))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                                        {{ t(['en' => 'Remove Rating', 'si' => 'ශ්‍රේණිගත කිරීම ඉවත් කරන්න']) }}
                                    </button>
                                </form>
                            @endif

                            <div class="space-y-3">
                                @forelse ($ratings as $rating)
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
                                        <div class="flex items-start gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700">
                                                {{ strtoupper(substr($rating->user->first_name, 0, 1)) }}{{ strtoupper(substr($rating->user->last_name, 0, 1)) }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="font-semibold text-slate-900">{{ $rating->user->full_name }}</p>
                                                    <span class="text-xs text-slate-500">{{ $rating->created_at->diffForHumans() }}</span>
                                                </div>
                                                <div class="mt-2 flex items-center gap-1">
                                                    @for ($star = 1; $star <= 5; $star++)
                                                        <svg class="h-4 w-4 {{ $star <= $rating->score ? 'text-amber-400' : 'text-slate-300' }}"
                                                            fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                                        </svg>
                                                    @endfor
                                                    <span class="ml-1 text-sm font-semibold text-slate-700">{{ $rating->score }}/5</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
                                        {{ t(['en' => 'No ratings yet. Be the first to rate this event!', 'si' => 'තවම ශ්‍රේණිගත කිරීම් නැත. මෙම ප්‍රසංගය ශ්‍රේණිගත කරන පළමු අයා වන්න!']) }}
                                    </div>
                                @endforelse
                            </div>
                        </section>
                        @endunless

                        {{-- Comments --}}
                        @unless ($isCancelled)
                        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8" x-data="{ editingId: null }">
                            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                                        <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-slate-900">{{ t(['en' => 'Comments', 'si' => 'අදහස්']) }}</h2>
                                        <p class="text-sm text-slate-500">{{ t(['en' => 'Share your thoughts about this event.', 'si' => 'මෙම ප්‍රසංගය පිළිබඳ ඔබේ අදහස් බෙදා ගන්න.']) }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex w-fit items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-700">
                                    {{ $comments->count() }} {{ t(['en' => 'total', 'si' => 'මුළු']) }}
                                </span>
                            </div>

                            <form action="{{ route('attendee.events.comments.store', $event) }}" method="POST" class="mb-6">
                                @csrf
                                <label for="comment-body" class="sr-only">{{ t(['en' => 'Add a comment', 'si' => 'අදහසක් එකතු කරන්න']) }}</label>
                                <textarea id="comment-body" name="body" rows="4" required maxlength="1000"
                                    placeholder="{{ t(['en' => 'Write your comment here...', 'si' => 'ඔබේ අදහස මෙහි ලියන්න...']) }}"
                                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('body') }}</textarea>
                                @error('body')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <div class="mt-4 flex justify-end">
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                        <i class="bi bi-send" aria-hidden="true"></i>
                                        {{ t(['en' => 'Add Comment', 'si' => 'අදහස එකතු කරන්න']) }}
                                    </button>
                                </div>
                            </form>

                            <div class="space-y-3">
                                @forelse ($comments as $comment)
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex items-start gap-3">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                                                    {{ strtoupper(substr($comment->user->first_name, 0, 1)) }}{{ strtoupper(substr($comment->user->last_name, 0, 1)) }}
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="text-sm font-medium text-slate-500">{{ $comment->user->full_name }}</p>
                                                        <span class="text-xs text-slate-400">
                                                            {{ $comment->created_at->diffForHumans() }}
                                                            @if ($comment->updated_at->gt($comment->created_at))
                                                                · {{ t(['en' => 'edited', 'si' => 'සංස්කරණය කළා']) }}
                                                            @endif
                                                        </span>
                                                    </div>

                                                    <div x-show="editingId !== {{ $comment->id }}"
                                                        class="mt-3 rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-2 text-base font-semibold leading-relaxed text-slate-900 ">
                                                        {{ $comment->body }}
                                                    </div>

                                                    @if ($comment->user_id === Auth::id())
                                                        <form x-show="editingId === {{ $comment->id }}" x-cloak
                                                            action="{{ route('attendee.events.comments.update', [$event, $comment]) }}"
                                                            method="POST" class="mt-3 space-y-3">
                                                            @csrf
                                                            @method('PUT')
                                                            <textarea name="body" rows="3" required maxlength="1000"
                                                                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-slate-800 focus:border-indigo-500 focus:ring-indigo-500">{{ $comment->body }}</textarea>
                                                            <div class="flex gap-2">
                                                                <button type="submit"
                                                                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                                                    {{ t(['en' => 'Save', 'si' => 'සුරකින්න']) }}
                                                                </button>
                                                                <button type="button" @click="editingId = null"
                                                                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                                                                    {{ t(['en' => 'Cancel', 'si' => 'අවලංගු කරන්න']) }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($comment->user_id === Auth::id())
                                                <div class="flex shrink-0 items-center gap-2">
                                                    <button type="button" x-show="editingId !== {{ $comment->id }}"
                                                        @click="editingId = {{ $comment->id }}"
                                                        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-50">
                                                        {{ t(['en' => 'Edit', 'si' => 'සංස්කරණය']) }}
                                                    </button>
                                                    <form action="{{ route('attendee.events.comments.destroy', [$event, $comment]) }}"
                                                        method="POST"
                                                        onsubmit="return confirm(@js(t(['en' => 'Delete this comment?', 'si' => 'මෙම අදහස මකන්නද?'])))">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                                            {{ t(['en' => 'Delete', 'si' => 'මකන්න']) }}
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
                                        {{ t(['en' => 'No comments yet. Be the first to share your thoughts!', 'si' => 'තවම අදහස් නැත. ඔබේ අදහස් බෙදා ගන්නා පළමු අයා වන්න!']) }}
                                    </div>
                                @endforelse
                            </div>
                        </section>
                        @endunless

                        {{-- Submit Inquiry --}}
                        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                                        <i class="bi bi-question-circle" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-slate-900">{{ t(['en' => 'Submit Inquiry', 'si' => 'විමසුමක් ඉදිරිපත් කරන්න']) }}</h2>
                                        <p class="text-sm text-slate-500">{{ t(['en' => 'Have a question about this event? Our team will get back to you.', 'si' => 'මෙම ප්‍රසංගය පිළිබඳ ප්‍රශ්නයක් තිබේද? අපගේ කණ්ඩායම ඔබ වෙත ආපසු පැමිණෙනු ඇත.']) }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('attendee.support.index', ['tab' => 'inquiries']) }}"
                                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                                    {{ t(['en' => 'View my inquiries', 'si' => 'මගේ විමසුම් බලන්න']) }}
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>

                            <form action="{{ route('attendee.events.inquiries.store', $event) }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="inquiry-subject" class="block text-sm font-semibold text-slate-700 mb-1">{{ t(['en' => 'Subject', 'si' => 'මාතෘකාව']) }}</label>
                                    <input type="text" id="inquiry-subject" name="subject" value="{{ old('subject') }}" required maxlength="255"
                                        placeholder="{{ t(['en' => 'What is your question about?', 'si' => 'ඔබේ ප්‍රශ්නය කුමක් පිළිබඳද?']) }}"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('subject')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="inquiry-message" class="block text-sm font-semibold text-slate-700 mb-1">{{ t(['en' => 'Message', 'si' => 'පණිවිඩය']) }}</label>
                                    <textarea id="inquiry-message" name="message" rows="4" required minlength="10" maxlength="2000"
                                        placeholder="{{ t(['en' => 'Provide details about your inquiry...', 'si' => 'ඔබේ විමසුම පිළිබඳ විස්තර ලබා දෙන්න...']) }}"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500">{{ old('message') }}</textarea>
                                    @error('message')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                        <i class="bi bi-send" aria-hidden="true"></i>
                                        {{ t(['en' => 'Submit Inquiry', 'si' => 'විමසුමක් ඉදිරිපත් කරන්න']) }}
                                    </button>
                                </div>
                            </form>
                        </section>
                    </div>

                    {{-- Sidebar: tickets --}}
                    <aside class="space-y-6 xl:sticky xl:top-24 xl:self-start">
                        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-5 text-white">
                                <h2 class="text-lg font-bold">{{ t(['en' => 'Ticket Categories', 'si' => 'ටිකට් ප්‍රවර්ග']) }}</h2>
                                <p class="mt-1 text-sm text-indigo-100">{{ t(['en' => 'Select a category and reserve your seats.', 'si' => 'වර්ගයක් තෝරා ඔබේ ආසන වෙන්කරවා ගන්න.']) }}</p>
                            </div>

                            <div class="space-y-4 p-5">
                                @if ($isCancelled)
                                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-8 text-center">
                                        <p class="text-sm font-semibold text-rose-800">{{ t(['en' => 'Booking unavailable', 'si' => 'වෙන්කිරීම ලබා ගත නොහැක']) }}</p>
                                        <p class="mt-2 text-sm text-rose-700">{{ t(['en' => 'This event has been cancelled by the organizer.', 'si' => 'මෙම ප්‍රසංගය සංවිධායකයා විසින් අවලංගු කර ඇත.']) }}</p>
                                    </div>
                                @elseif ($isCompleted)
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-8 text-center">
                                        <p class="text-sm font-semibold text-slate-800">{{ t(['en' => 'Booking closed', 'si' => 'වෙන්කිරීම වසා ඇත']) }}</p>
                                        <p class="mt-2 text-sm text-slate-600">{{ t(['en' => 'This event has ended. View your tickets in My Tickets.', 'si' => 'මෙම ප්‍රසංගය අවසන් වී ඇත. මගේ ටිකට් තුළ ඔබේ ටිකට් බලන්න.']) }}</p>
                                        <a href="{{ route('attendee.bookings.index') }}"
                                            class="mt-4 inline-flex rounded-xl bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                            {{ t(['en' => 'View My Tickets', 'si' => 'මගේ ටිකට් බලන්න']) }}
                                        </a>
                                    </div>
                                @else
                                @forelse($ticketCategories as $category)
                                    @php
                                        $available = (int) ($category->no_of_available_tickets ?? 0);
                                        $total = max((int) ($category->no_of_tickets ?? 0), 1);
                                        $soldPercent = min(100, max(0, (($total - $available) / $total) * 100));
                                        $isSoldOut = $available <= 0;
                                    @endphp

                                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/40 transition hover:border-slate-300 hover:shadow-md">
                                        <div class="h-1.5" style="background-color: {{ $category->ticket_color }}"></div>

                                        <div class="p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h3 class="font-bold text-slate-900">{{ $category->name }}</h3>
                                                        @if ($isSoldOut)
                                                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700">{{ t(['en' => 'Sold out', 'si' => 'විකුණා අවසන්']) }}</span>
                                                        @endif
                                                    </div>
                                                    @if ($category->description)
                                                        <p class="mt-1 text-sm text-slate-500">{{ $category->description }}</p>
                                                    @endif
                                                </div>
                                                <span class="h-4 w-4 shrink-0 rounded-full ring-2 ring-white"
                                                    style="background-color: {{ $category->ticket_color }}"></span>
                                            </div>

                                            <div class="mt-4 flex items-end justify-between gap-3">
                                                <div>
                                                    <p class="text-2xl font-bold text-indigo-600">Rs {{ number_format($category->ticket_price) }}</p>
                                                    <p class="text-xs text-slate-500">{{ t(['en' => 'per ticket', 'si' => 'ටිකට් එකකට']) }}</p>
                                                </div>
                                                <div class="text-right text-xs text-slate-500">
                                                    <span class="font-semibold text-emerald-600">{{ number_format($available) }}</span> {{ t(['en' => 'left', 'si' => 'ඉතිරි']) }}
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                                                    <div class="h-full rounded-full transition-all"
                                                        style="width: {{ $soldPercent }}%; background-color: {{ $category->ticket_color }}"></div>
                                                </div>
                                                <p class="mt-1 text-[11px] text-slate-500">{{ number_format($available) }} {{ t(['en' => 'of', 'si' => 'න්']) }} {{ number_format($category->no_of_tickets ?? 0) }} {{ t(['en' => 'available', 'si' => 'ලබා ගත හැකි']) }}</p>
                                            </div>

                                            <button type="button"
                                                @if (!$isSoldOut)
                                                    @click="selected = { id: {{ $category->id }}, name: {{ json_encode($category->name) }}, price: {{ $category->ticket_price }}, available: {{ $available }}, color: {{ json_encode($category->ticket_color) }} }; qty = 1; amount = (selected.price * 1).toFixed(2); showModal = true"
                                                @endif
                                                @disabled($isSoldOut)
                                                class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold transition
                                                {{ $isSoldOut ? 'cursor-not-allowed bg-slate-200 text-slate-500' : 'bg-indigo-600 text-white hover:bg-indigo-700' }}">
                                                <i class="bi bi-cart-plus" aria-hidden="true"></i>
                                                {{ $isSoldOut ? t(['en' => 'Unavailable', 'si' => 'ලබා ගත නොහැක']) : t(['en' => 'Reserve Tickets', 'si' => 'ටිකට් වෙන්කරවා ගන්න']) }}
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center text-sm text-slate-500">
                                        {{ t(['en' => 'No ticket categories available yet.', 'si' => 'තවම ටිකට් වර්ගය ලබා ගත නොහැක.']) }}
                                    </div>
                                @endforelse
                                @endif
                            </div>
                        </section>

                        @if ($eventCartItems->isNotEmpty())
                            <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-100 px-6 py-5">
                                    <h2 class="text-lg font-bold text-slate-900">{{ t(['en' => 'Your Reserved Tickets', 'si' => 'ඔබේ වෙන්කර ගත් ටිකට්']) }}</h2>
                                    <p class="mt-1 text-sm text-slate-500">{{ t(['en' => 'Tickets held for this event.', 'si' => 'මෙම ප්‍රසංගය සඳහා රඳවා ඇති ටිකට්.']) }}</p>
                                </div>

                                <div class="divide-y divide-slate-100">
                                    @foreach ($eventCartItems as $item)
                                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full"
                                                        style="background-color: {{ $item->ticketCategory->ticket_color }}"></span>
                                                    <p class="truncate font-semibold text-slate-900">{{ $item->ticketCategory->name }}</p>
                                                </div>
                                                <p class="mt-1 text-sm text-slate-500">
                                                    {{ $item->quantity }} × Rs {{ number_format($item->unit_price, 2) }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-indigo-600">Rs {{ number_format($item->line_total, 2) }}</p>
                                                <form action="{{ route('attendee.cart.destroy', $item) }}" method="POST" class="mt-1"
                                                    onsubmit="return confirm(@js(t(['en' => 'Remove this reserved ticket?', 'si' => 'මෙම වෙන්කර ගත් ටිකට් ඉවත් කරන්නද?'])))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700">
                                                        {{ t(['en' => 'Remove', 'si' => 'ඉවත් කරන්න']) }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="border-t border-slate-100 bg-slate-50 px-5 py-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-slate-500">{{ t(['en' => 'Event subtotal', 'si' => 'ප්‍රසංගයේ සම්පූර්ණ එකතුව']) }}</span>
                                        <span class="text-xl font-bold text-indigo-600">Rs {{ number_format($eventCartTotal, 2) }}</span>
                                    </div>
                                    <a href="{{ route('attendee.cart.index') }}"
                                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                        <i class="bi bi-bag-check" aria-hidden="true"></i>
                                        Go to Cart
                                    </a>
                                </div>
                            </section>
                        @endif
                    </aside>
                </div>

                {{-- Booking modal --}}
                <div x-show="showModal" x-cloak style="display:none;" @keydown.escape.window="showModal = false"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>

                    <div class="relative z-50 w-full max-w-md overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl">
                        <div class="h-1.5" :style="'background-color:' + (selected.color || '#4f46e5')"></div>

                        <form action="{{ route('attendee.cart.store', $event) }}" method="POST" class="p-6">
                            @csrf

                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t(['en' => 'Reserve tickets', 'si' => 'ටිකට් වෙන්කරවා ගන්න']) }}</p>
                                    <h3 class="mt-1 text-xl font-bold text-slate-900" x-text="selected.name"></h3>
                                </div>
                                <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                    @click="showModal = false" aria-label="{{ t(['en' => 'Close', 'si' => 'වසන්න']) }}">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </button>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-4 rounded-2xl bg-slate-50 p-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t(['en' => 'Price', 'si' => 'මිල']) }}</p>
                                    <p class="mt-1 text-lg font-bold text-slate-900">Rs <span x-text="Number(selected.price).toFixed(2)"></span></p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t(['en' => 'Available', 'si' => 'ලබා ගත හැකි']) }}</p>
                                    <p class="mt-1 text-lg font-bold text-emerald-600" x-text="selected.available"></p>
                                </div>
                            </div>

                            <input type="hidden" name="ticket_category_id" :value="selected.id">

                            <div class="mt-5">
                                <label class="text-sm font-semibold text-slate-700">{{ t(['en' => 'Number of tickets', 'si' => 'ටිකට් ගණන']) }}</label>
                                <input type="number" name="quantity" x-model.number="qty" min="1"
                                    :max="selected.available"
                                    class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500"
                                    @input="amount = (qty * selected.price).toFixed(2)">
                            </div>

                            <div class="mt-5 flex items-center justify-between rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3">
                                <span class="text-sm font-medium text-indigo-900">{{ t(['en' => 'Total amount', 'si' => 'මුළු මුදල']) }}</span>
                                <span class="text-xl font-bold text-indigo-700">Rs <span x-text="amount"></span></span>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-3">
                                <button type="button"
                                    class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                    @click="showModal = false">
                                    {{ t(['en' => 'Cancel', 'si' => 'අවලංගු කරන්න']) }}
                                </button>
                                <button type="submit"
                                    class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                    {{ t(['en' => 'Reserve Tickets', 'si' => 'ටිකට් වෙන්කරවා ගන්න']) }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

</x-app-layout>
