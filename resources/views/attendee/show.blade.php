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
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <a href="{{ route('attendee.dashboard') }}"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Back to events
                </a>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                    {{ $event->name }}
                </h2>
                <p class="mt-1 text-slate-500">
                    View details, reserve tickets, and join the conversation.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @unless ($isCancelled || $isCompleted)
                <form action="{{ route('attendee.events.like', $event) }}" method="POST">
                    @csrf
                    <button type="submit"
                        aria-label="{{ $isLiked ? __('Unlike event') : __('Like event') }}"
                        class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition
                        {{ $isLiked ? 'border-[#1877F2] bg-[#1877F2] text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300' }}">
                        <i class="bi {{ $isLiked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}" aria-hidden="true"></i>
                        {{ number_format($likesCount) }}
                    </button>
                </form>

                <form action="{{ route('attendee.events.save', $event) }}" method="POST">
                    @csrf
                    <button type="submit"
                        aria-label="{{ $isSaved ? __('Unsave event') : __('Save event') }}"
                        class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition
                        {{ $isSaved ? 'border-amber-500 bg-amber-500 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300' }}">
                        <i class="bi {{ $isSaved ? 'bi-bookmark-fill' : 'bi-bookmark' }}" aria-hidden="true"></i>
                        {{ $isSaved ? __('Saved') : __('Save') }}
                    </button>
                </form>
                @endunless
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div x-data="{ showModal: false, selected: { id: null, name: '', price: 0, available: 0, color: '' }, qty: 1, amount: 0 }">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">

                @if ($errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-5">
                        <div class="flex gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-red-800">Something went wrong</h3>
                                <ul class="mt-2 space-y-1 text-sm text-red-700">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-emerald-800">Success</h3>
                                    <p class="mt-1 text-sm text-emerald-700">{{ session('success') }}</p>
                                </div>
                            </div>
                            <button @click="show=false" class="text-emerald-600 hover:text-emerald-800" aria-label="Dismiss">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                @endif

                @if ($isCancelled)
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
                        <div class="flex gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                                <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-rose-900">Event Cancelled</h3>
                                @if ($event->cancellation_reason)
                                    <p class="mt-2 text-sm leading-relaxed text-rose-800">{{ $event->cancellation_reason }}</p>
                                @endif
                                <p class="mt-3 text-sm text-rose-700">
                                    This event is no longer available for booking or interaction. If you purchased tickets, refunds have been processed to your wallet.
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif ($isCompleted)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-200 text-slate-700">
                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900">Event Completed</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-700">
                                    This event has ended. You can still review event details, your ticket history, and any comments, likes, or ratings you submitted.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Hero --}}
                <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                    <div class="relative">
                        @if ($event->cover)
                            <img src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                alt="{{ $event->name }}"
                                class="h-[360px] w-full object-cover sm:h-[420px]">
                        @else
                            <div class="h-[360px] w-full bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 sm:h-[420px]"></div>
                        @endif

                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/35 to-transparent"></div>

                        <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white backdrop-blur">
                                    {{ $event->eventCategory->name ?? 'Event' }}
                                </span>
                                <span class="rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide backdrop-blur {{ $statusClass }}">
                                    {{ $isCancelled ? 'Event Cancelled' : ($isCompleted ? 'Completed' : ucfirst($eventStatus)) }}
                                </span>
                            </div>

                            <h1 class="mt-4 max-w-4xl text-3xl font-bold tracking-tight text-white sm:text-5xl">
                                {{ $event->name }}
                            </h1>

                            <div class="mt-5 flex flex-wrap gap-x-6 gap-y-3 text-sm text-white/90 sm:text-base">
                                <span class="inline-flex items-center gap-2">
                                    <i class="bi bi-geo-alt-fill text-white/80" aria-hidden="true"></i>
                                    {{ $event->place }}
                                </span>
                                <span class="inline-flex items-center gap-2">
                                    <i class="bi bi-calendar-event text-white/80" aria-hidden="true"></i>
                                    {{ $formattedDate }}
                                </span>
                                @if ($event->time)
                                    <span class="inline-flex items-center gap-2">
                                        <i class="bi bi-clock text-white/80" aria-hidden="true"></i>
                                        {{ $event->time }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Quick stats strip --}}
                    <div class="grid divide-y divide-slate-100 sm:grid-cols-2 lg:grid-cols-5 lg:divide-x lg:divide-y-0">
                        <div class="flex items-center gap-4 px-6 py-5">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                                <i class="bi bi-person-badge text-lg" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Host</p>
                                <p class="mt-0.5 font-semibold text-slate-900">{{ $event->host->name ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 px-6 py-5">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                <i class="bi bi-ticket-perforated text-lg" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Capacity</p>
                                <p class="mt-0.5 font-semibold text-slate-900">{{ number_format($event->total_tickets) }} tickets</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 px-6 py-5">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                                <i class="bi bi-headset text-lg" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</p>
                                <p class="mt-0.5 font-semibold text-slate-900">{{ $event->contactPerson->full_name ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 px-6 py-5">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                                <i class="bi bi-star-fill text-lg" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rating</p>
                                <p class="mt-0.5 font-semibold text-slate-900">
                                    @if ($ratingsCount > 0)
                                        {{ number_format($averageRating, 1) }}/5 · {{ $ratingsCount }} {{ __('reviews') }}
                                    @else
                                        {{ __('No ratings yet') }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 px-6 py-5">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#1877F2]/10 text-[#1877F2]">
                                <i class="bi bi-hand-thumbs-up-fill text-lg" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Likes</p>
                                <p class="mt-0.5 font-semibold text-slate-900">{{ number_format($likesCount) }}</p>
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
                                    <h2 class="text-xl font-bold text-slate-900">About This Event</h2>
                                    <p class="text-sm text-slate-500">Everything you need to know before you book.</p>
                                </div>
                            </div>
                            <div class="prose prose-slate max-w-none text-slate-600">
                                <p class="leading-relaxed whitespace-pre-line">{{ $event->description ?: 'No description provided for this event yet.' }}</p>
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
                                        <h2 class="text-xl font-bold text-slate-900">{{ __('Ratings') }}</h2>
                                        <p class="text-sm text-slate-500">{{ __('Rate this event and see what others think.') }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex w-fit items-center rounded-full bg-amber-50 px-4 py-1.5 text-sm font-semibold text-amber-700">
                                    @if ($ratingsCount > 0)
                                        {{ number_format($averageRating, 1) }} ★ · {{ $ratingsCount }} {{ __('total') }}
                                    @else
                                        {{ __('No ratings yet') }}
                                    @endif
                                </span>
                            </div>

                            <form action="{{ route('attendee.events.ratings.store', $event) }}" method="POST"
                                class="mb-6 rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                                @csrf
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ $userRating ? __('Update your rating') : __('Rate this event') }}
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
                                        {{ $userRating ? __('Update Rating') : __('Submit Rating') }}
                                    </button>
                                </div>
                                @error('score')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @if ($userRating)
                                    <p class="mt-2 text-sm text-slate-500">{{ __('Your current rating:') }} {{ $userRating }}/5</p>
                                @endif
                            </form>

                            @if ($userRating)
                                <form action="{{ route('attendee.events.ratings.destroy', $event) }}" method="POST"
                                    class="mb-6 -mt-2"
                                    onsubmit="return confirm(@js(__('Remove your rating?')))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                                        {{ __('Remove Rating') }}
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
                                        {{ __('No ratings yet. Be the first to rate this event!') }}
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
                                        <h2 class="text-xl font-bold text-slate-900">{{ __('Comments') }}</h2>
                                        <p class="text-sm text-slate-500">{{ __('Share your thoughts about this event.') }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex w-fit items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-700">
                                    {{ $comments->count() }} {{ __('total') }}
                                </span>
                            </div>

                            <form action="{{ route('attendee.events.comments.store', $event) }}" method="POST" class="mb-6">
                                @csrf
                                <label for="comment-body" class="sr-only">{{ __('Add a comment') }}</label>
                                <textarea id="comment-body" name="body" rows="4" required maxlength="1000"
                                    placeholder="{{ __('Write your comment here...') }}"
                                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('body') }}</textarea>
                                @error('body')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <div class="mt-4 flex justify-end">
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                        <i class="bi bi-send" aria-hidden="true"></i>
                                        {{ __('Add Comment') }}
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
                                                                · {{ __('edited') }}
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
                                                                    {{ __('Save') }}
                                                                </button>
                                                                <button type="button" @click="editingId = null"
                                                                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                                                                    {{ __('Cancel') }}
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
                                                        {{ __('Edit') }}
                                                    </button>
                                                    <form action="{{ route('attendee.events.comments.destroy', [$event, $comment]) }}"
                                                        method="POST"
                                                        onsubmit="return confirm(@js(__('Delete this comment?')))">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                                            {{ __('Delete') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
                                        {{ __('No comments yet. Be the first to share your thoughts!') }}
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
                                        <h2 class="text-xl font-bold text-slate-900">{{ __('Submit Inquiry') }}</h2>
                                        <p class="text-sm text-slate-500">{{ __('Have a question about this event? Our team will get back to you.') }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('attendee.support.index', ['tab' => 'inquiries']) }}"
                                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                                    {{ __('View my inquiries') }}
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>

                            <form action="{{ route('attendee.events.inquiries.store', $event) }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="inquiry-subject" class="block text-sm font-semibold text-slate-700 mb-1">{{ __('Subject') }}</label>
                                    <input type="text" id="inquiry-subject" name="subject" value="{{ old('subject') }}" required maxlength="255"
                                        placeholder="{{ __('What is your question about?') }}"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('subject')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="inquiry-message" class="block text-sm font-semibold text-slate-700 mb-1">{{ __('Message') }}</label>
                                    <textarea id="inquiry-message" name="message" rows="4" required minlength="10" maxlength="2000"
                                        placeholder="{{ __('Provide details about your inquiry...') }}"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500">{{ old('message') }}</textarea>
                                    @error('message')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                        <i class="bi bi-send" aria-hidden="true"></i>
                                        {{ __('Submit Inquiry') }}
                                    </button>
                                </div>
                            </form>
                        </section>
                    </div>

                    {{-- Sidebar: tickets --}}
                    <aside class="space-y-6 xl:sticky xl:top-24 xl:self-start">
                        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-100 bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-5 text-white">
                                <h2 class="text-lg font-bold">Ticket Categories</h2>
                                <p class="mt-1 text-sm text-indigo-100">Select a category and reserve your seats.</p>
                            </div>

                            <div class="space-y-4 p-5">
                                @if ($isCancelled)
                                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-8 text-center">
                                        <p class="text-sm font-semibold text-rose-800">Booking unavailable</p>
                                        <p class="mt-2 text-sm text-rose-700">This event has been cancelled by the organizer.</p>
                                    </div>
                                @elseif ($isCompleted)
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-8 text-center">
                                        <p class="text-sm font-semibold text-slate-800">Booking closed</p>
                                        <p class="mt-2 text-sm text-slate-600">This event has ended. View your tickets in My Tickets.</p>
                                        <a href="{{ route('attendee.bookings.index') }}"
                                            class="mt-4 inline-flex rounded-xl bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                            View My Tickets
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
                                                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700">Sold out</span>
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
                                                    <p class="text-xs text-slate-500">per ticket</p>
                                                </div>
                                                <div class="text-right text-xs text-slate-500">
                                                    <span class="font-semibold text-emerald-600">{{ number_format($available) }}</span> left
                                                </div>
                                            </div>

                                            <div class="mt-3">
                                                <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                                                    <div class="h-full rounded-full transition-all"
                                                        style="width: {{ $soldPercent }}%; background-color: {{ $category->ticket_color }}"></div>
                                                </div>
                                                <p class="mt-1 text-[11px] text-slate-500">{{ number_format($available) }} of {{ number_format($category->no_of_tickets ?? 0) }} available</p>
                                            </div>

                                            <button type="button"
                                                @if (!$isSoldOut)
                                                    @click="selected = { id: {{ $category->id }}, name: {{ json_encode($category->name) }}, price: {{ $category->ticket_price }}, available: {{ $available }}, color: {{ json_encode($category->ticket_color) }} }; qty = 1; amount = (selected.price * 1).toFixed(2); showModal = true"
                                                @endif
                                                @disabled($isSoldOut)
                                                class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold transition
                                                {{ $isSoldOut ? 'cursor-not-allowed bg-slate-200 text-slate-500' : 'bg-indigo-600 text-white hover:bg-indigo-700' }}">
                                                <i class="bi bi-cart-plus" aria-hidden="true"></i>
                                                {{ $isSoldOut ? 'Unavailable' : 'Reserve Tickets' }}
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center text-sm text-slate-500">
                                        No ticket categories available yet.
                                    </div>
                                @endforelse
                                @endif
                            </div>
                        </section>

                        @if ($eventCartItems->isNotEmpty())
                            <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-100 px-6 py-5">
                                    <h2 class="text-lg font-bold text-slate-900">Your Reserved Tickets</h2>
                                    <p class="mt-1 text-sm text-slate-500">Tickets held for this event.</p>
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
                                                    onsubmit="return confirm('Remove this reserved ticket?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700">
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="border-t border-slate-100 bg-slate-50 px-5 py-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-slate-500">Event subtotal</span>
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
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reserve tickets</p>
                                    <h3 class="mt-1 text-xl font-bold text-slate-900" x-text="selected.name"></h3>
                                </div>
                                <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                    @click="showModal = false" aria-label="Close">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </button>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-4 rounded-2xl bg-slate-50 p-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Price</p>
                                    <p class="mt-1 text-lg font-bold text-slate-900">Rs <span x-text="Number(selected.price).toFixed(2)"></span></p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Available</p>
                                    <p class="mt-1 text-lg font-bold text-emerald-600" x-text="selected.available"></p>
                                </div>
                            </div>

                            <input type="hidden" name="ticket_category_id" :value="selected.id">

                            <div class="mt-5">
                                <label class="text-sm font-semibold text-slate-700">Number of tickets</label>
                                <input type="number" name="quantity" x-model.number="qty" min="1"
                                    :max="selected.available"
                                    class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-indigo-500 focus:ring-indigo-500"
                                    @input="amount = (qty * selected.price).toFixed(2)">
                            </div>

                            <div class="mt-5 flex items-center justify-between rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3">
                                <span class="text-sm font-medium text-indigo-900">Total amount</span>
                                <span class="text-xl font-bold text-indigo-700">Rs <span x-text="amount"></span></span>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-3">
                                <button type="button"
                                    class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                    @click="showModal = false">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                    Reserve Tickets
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

</x-app-layout>
