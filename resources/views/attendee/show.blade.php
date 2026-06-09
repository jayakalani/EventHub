<x-app-layout>

    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">

            <div>
                <h2 class="text-3xl font-bold text-slate-900">
                    Event Details
                </h2>
                <p class="text-slate-500 mt-1">
                    event information, ticket categories and bookings.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">

        <div x-data="{ showModal: false, selected: { id: null, name: '', price: 0, available: 0, color: '' }, qty: 1, amount: 0 }">
            <div class="max-w-7xl mx-auto px-6 space-y-8">

                {{-- ERROR ALERT --}}
                @if ($errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-5">

                        <div class="flex gap-3">

                            <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                                ⚠️
                            </div>

                            <div>

                                <h3 class="font-semibold text-red-800">
                                    Something went wrong
                                </h3>

                                <ul class="mt-2 text-sm text-red-700 space-y-1">

                                    @foreach ($errors->all() as $error)
                                        <li>• {{ $error }}</li>
                                    @endforeach

                                </ul>

                            </div>

                        </div>

                    </div>
                @endif

                {{-- SUCCESS ALERT --}}
                @if (session('success'))
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                        class="rounded-2xl border border-green-200 bg-green-50 p-5">

                        <div class="flex justify-between items-center">

                            <div class="flex items-center gap-3">

                                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                    ✓
                                </div>

                                <div>

                                    <h3 class="font-semibold text-green-800">
                                        Success
                                    </h3>

                                    <p class="text-sm text-green-700">
                                        {{ session('success') }}
                                    </p>

                                </div>

                            </div>

                            <button @click="show=false" class="text-green-600 hover:text-green-800">
                                ✕
                            </button>

                        </div>

                    </div>
                @endif

                {{-- EVENT HERO --}}
                <div class="relative overflow-hidden rounded-[32px] shadow-xl">

                    @if ($event->cover)
                        <img src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                            class="h-[420px] w-full object-cover">
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

                    <div class="absolute bottom-0 left-0 right-0 p-8">

                        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-6">

                            <div>

                                <span class="rounded-full bg-white/20 backdrop-blur px-4 py-1 text-white text-sm">
                                    {{ $event->eventCategory->name ?? 'Category' }}
                                </span>

                                <h1 class="mt-4 text-5xl font-bold text-white">
                                    {{ $event->name }}
                                </h1>

                                <div class="mt-4 flex flex-wrap gap-5 text-white/90">

                                    <span>📍 {{ $event->place }}</span>

                                    <span>
                                        📅 {{ \Carbon\Carbon::parse($event->date)->format('d M Y') }}
                                    </span>

                                    <span>
                                        🕒 {{ $event->time }}
                                    </span>

                                </div>

                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <span
                                    class="rounded-full px-5 py-2 text-sm font-semibold
                                {{ $event->status === 'ongoing' ? 'bg-green-500 text-white' : 'bg-white/20 backdrop-blur text-white' }}">

                                    {{ ucfirst($event->status) }}

                                </span>

                                <form action="{{ route('attendee.events.like', $event) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        aria-label="{{ $isLiked ? __('Unlike event') : __('Like event') }}"
                                        title="{{ $isLiked ? __('Unlike') : __('Like') }}"
                                        class="inline-flex items-center gap-2 transition hover:opacity-90">
                                        <span
                                            class="inline-flex items-center justify-center rounded-full p-3 text-2xl
                                            {{ $isLiked ? 'bg-[#1877F2] text-white' : 'bg-white/20 backdrop-blur text-white' }}">
                                            <i class="bi {{ $isLiked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"
                                                aria-hidden="true"></i>
                                        </span>
                                        <span
                                            class="rounded-full bg-black/30 px-2.5 py-1 text-xs font-semibold text-white">{{ $likesCount }}</span>
                                    </button>
                                </form>

                                <form action="{{ route('attendee.events.save', $event) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        aria-label="{{ $isSaved ? __('Unsave event') : __('Save event') }}"
                                        title="{{ $isSaved ? __('Unsave') : __('Save') }}"
                                        class="inline-flex items-center justify-center rounded-full p-3 text-2xl transition
                                        {{ $isSaved ? 'bg-amber-500 text-white hover:bg-amber-600' : 'bg-white/20 backdrop-blur text-white hover:bg-white/30' }}">
                                        <i class="bi {{ $isSaved ? 'bi-bookmark-fill' : 'bi-bookmark' }}"
                                            aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>

                        </div>

                    </div>

                </div>

                {{-- STATS --}}
                <div class="grid md:grid-cols-2 lg:grid-cols-6 gap-5">

                    <div class="bg-white border rounded-3xl p-6 shadow-sm">
                        <p class="text-sm text-slate-500">Hosted By</p>
                        <h3 class="mt-2 text-lg font-semibold">
                            {{ $event->host->name ?? 'N/A' }}
                        </h3>
                    </div>

                    <div class="bg-white border rounded-3xl p-6 shadow-sm">
                        <p class="text-sm text-slate-500">Total tickets</p>
                        <h3 class="mt-2 text-lg font-semibold">
                            {{ number_format($event->total_tickets) }}
                        </h3>
                    </div>

                    <div class="bg-white border rounded-3xl p-6 shadow-sm">
                        <p class="text-sm text-slate-500">Contact Person</p>
                        <h3 class="mt-2 text-lg font-semibold">
                            {{ $event->contactPerson->name ?? 'N/A' }}
                        </h3>
                    </div>

                    <div class="bg-white border rounded-3xl p-6 shadow-sm">
                        <p class="text-sm text-slate-500">Likes</p>
                        <h3 class="mt-2 inline-flex items-center gap-2 text-lg font-semibold text-[#1877F2]">
                            <i class="bi bi-hand-thumbs-up-fill text-xl" aria-hidden="true"></i>
                            {{ number_format($likesCount) }}
                        </h3>
                    </div>

                    <div class="bg-white border rounded-3xl p-6 shadow-sm">
                        <p class="text-sm text-slate-500">{{ __('Rating') }}</p>
                        <h3 class="mt-2 text-lg font-semibold text-yellow-600">
                            @if ($ratingsCount > 0)
                                {{ number_format($averageRating, 1) }} / 5
                            @else
                                {{ __('No ratings') }}
                            @endif
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">{{ $ratingsCount }} {{ __('reviews') }}</p>
                    </div>

                    <div class="bg-white border rounded-3xl p-6 shadow-sm">
                        <p class="text-sm text-slate-500">Status</p>
                        <h3 class="mt-2 text-lg font-semibold">
                            {{ ucfirst($event->status) }}
                        </h3>
                    </div>

                </div>

                {{-- DESCRIPTION --}}
                <div class="bg-white border rounded-3xl p-8 shadow-sm">

                    <h2 class="text-2xl font-bold text-slate-900 mb-4">
                        About This Event
                    </h2>

                    <p class="leading-relaxed text-slate-600">
                        {{ $event->description }}
                    </p>

                </div>

                {{-- RATINGS --}}
                <div class="bg-white border rounded-3xl p-8 shadow-sm" x-data="{ hover: 0, selected: {{ $userRating ?? 0 }} }">
                    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900">
                                {{ __('Ratings') }}
                            </h2>
                            <p class="text-slate-500">
                                {{ __('Rate this event and see what others think.') }}
                            </p>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full bg-yellow-50 px-4 py-1.5 text-sm font-semibold text-yellow-700">
                            @if ($ratingsCount > 0)
                                {{ number_format($averageRating, 1) }} ★ · {{ $ratingsCount }} {{ __('total') }}
                            @else
                                {{ __('No ratings yet') }}
                            @endif
                        </span>
                    </div>

                    <form action="{{ route('attendee.events.ratings.store', $event) }}" method="POST"
                        class="mb-8 rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
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
                                        class="rounded-lg p-1 transition hover:scale-110 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                                        <svg class="h-8 w-8"
                                            :class="(hover || selected) >= {{ $star }} ? 'text-yellow-400' : 'text-slate-300'"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                        </svg>
                                    </button>
                                @endfor
                            </div>
                            <input type="hidden" name="score" :value="selected" required>
                            <button type="submit" :disabled="selected < 1"
                                class="inline-flex items-center rounded-xl bg-yellow-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-yellow-600 disabled:cursor-not-allowed disabled:opacity-50">
                                {{ $userRating ? __('Update Rating') : __('Submit Rating') }}
                            </button>
                        </div>
                        @error('score')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @if ($userRating)
                            <p class="mt-2 text-sm text-slate-500">
                                {{ __('Your current rating:') }} {{ $userRating }}/5
                            </p>
                        @endif
                    </form>
                    @if ($userRating)
                        <form action="{{ route('attendee.events.ratings.destroy', $event) }}" method="POST"
                            class="mb-8 -mt-4"
                            onsubmit="return confirm(@js(__('Remove your rating?')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                                {{ __('Remove Rating') }}
                            </button>
                        </form>
                    @endif

                    <div class="space-y-4">
                        @forelse ($ratings as $rating)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-yellow-100 text-sm font-bold text-yellow-700">
                                        {{ strtoupper(substr($rating->user->first_name, 0, 1)) }}{{ strtoupper(substr($rating->user->last_name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold text-slate-900">{{ $rating->user->full_name }}</p>
                                            <span class="text-xs text-slate-500">{{ $rating->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="mt-2 flex items-center gap-1">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <svg class="h-4 w-4 {{ $star <= $rating->score ? 'text-yellow-400' : 'text-slate-300' }}"
                                                    fill="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
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
                </div>

                {{-- COMMENTS --}}
                <div class="bg-white border rounded-3xl p-8 shadow-sm" x-data="{ editingId: null }">
                    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900">
                                {{ __('Comments') }}
                            </h2>
                            <p class="text-slate-500">
                                {{ __('Share your thoughts about this event.') }}
                            </p>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-700">
                            {{ $comments->count() }} {{ __('total') }}
                        </span>
                    </div>

                    <form action="{{ route('attendee.events.comments.store', $event) }}" method="POST" class="mb-8">
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
                                class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                {{ __('Add Comment') }}
                            </button>
                        </div>
                    </form>

                    <div class="space-y-4">
                        @forelse ($comments as $comment)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                                            {{ strtoupper(substr($comment->user->first_name, 0, 1)) }}{{ strtoupper(substr($comment->user->last_name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-semibold text-slate-900">{{ $comment->user->full_name }}</p>
                                                <span class="text-xs text-slate-500">
                                                    {{ $comment->created_at->diffForHumans() }}
                                                    @if ($comment->updated_at->gt($comment->created_at))
                                                        · {{ __('edited') }}
                                                    @endif
                                                </span>
                                            </div>

                                            <div x-show="editingId !== {{ $comment->id }}" class="mt-2 text-slate-700 whitespace-pre-wrap">
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
                </div>

                {{-- ticket CATEGORIES --}}
                <div>

                    <div class="mb-6">

                        <h2 class="text-2xl font-bold text-slate-900">
                            ticket Categories
                        </h2>

                        <p class="text-slate-500">
                            Choose your preferred ticketing category.
                        </p>

                    </div>

                    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">

                        @forelse($ticketCategories as $category)
                            <div
                                class="bg-white border rounded-[28px] overflow-hidden shadow-sm hover:shadow-xl transition duration-300">

                                <div class="h-2" style="background-color: {{ $category->ticket_color }}"></div>

                                <div class="p-6">

                                    <div class="flex justify-between items-start">

                                        <div>

                                            <h3 class="text-xl font-bold">
                                                {{ $category->name }}
                                            </h3>

                                            <p class="text-sm text-slate-500 mt-1">
                                                {{ $category->description }}
                                            </p>

                                        </div>

                                        <div class="w-5 h-5 rounded-full border"
                                            style="background-color: {{ $category->ticket_color }}">
                                        </div>

                                    </div>

                                    <div class="mt-6">

                                        <div class="text-4xl font-bold text-indigo-600">
                                            Rs {{ number_format($category->ticket_price) }}
                                        </div>

                                        <div class="text-sm text-slate-500">
                                            per ticket
                                        </div>

                                    </div>

                                    <div class="mt-6 space-y-3">

                                        <div class="flex justify-between">
                                            <span>Total tickets</span>
                                            <span class="font-semibold">
                                                {{ number_format($category->no_of_tickets ?? 0) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span>Available</span>
                                            <span class="font-semibold text-green-600">
                                                {{ number_format($category->no_of_available_tickets ?? 0) }}
                                            </span>
                                        </div>

                                    </div>

                                    <button type="button"
                                        @click="selected = { id: {{ $category->id }}, name: {{ json_encode($category->name) }}, price: {{ $category->ticket_price }}, available: {{ $category->no_of_available_tickets }}, color: {{ json_encode($category->ticket_color) }} }; qty = 1; amount = (selected.price * 1).toFixed(2); showModal = true"
                                        class="mt-6 w-full rounded-2xl bg-indigo-600 py-3 font-semibold text-white hover:bg-indigo-700 transition">

                                        Book Now

                                    </button>

                                </div>

                            </div>

                        @empty

                            <div
                                class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">

                                <p class="text-slate-500">
                                    No ticket categories available yet.
                                </p>

                            </div>
                        @endforelse

                    </div>

                </div>

                {{-- RESERVED TICKETS FOR THIS EVENT --}}
                @if ($eventCartItems->isNotEmpty())
                    <div class="bg-white border rounded-[28px] p-6 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-slate-900">Your Reserved Tickets</h2>
                                <p class="text-slate-500 mt-1">Tickets you reserved for this event.</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-slate-500">Event subtotal</p>
                                <p class="text-2xl font-bold text-indigo-600">Rs {{ number_format($eventCartTotal, 2) }}</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        <th class="px-4 py-3">Category</th>
                                        <th class="px-4 py-3">Price</th>
                                        <th class="px-4 py-3">Qty</th>
                                        <th class="px-4 py-3">Total</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($eventCartItems as $item)
                                        <tr>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center gap-3">
                                                    <span class="h-3 w-3 rounded-full"
                                                        style="background-color: {{ $item->ticketCategory->ticket_color }}"></span>
                                                    <span class="font-semibold text-slate-900">{{ $item->ticketCategory->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-slate-600">Rs {{ number_format($item->unit_price, 2) }}</td>
                                            <td class="px-4 py-4 text-slate-900 font-semibold">{{ $item->quantity }}</td>
                                            <td class="px-4 py-4 font-semibold text-indigo-600">Rs {{ number_format($item->line_total, 2) }}</td>
                                            <td class="px-4 py-4 text-right">
                                                <form action="{{ route('attendee.cart.destroy', $item) }}" method="POST"
                                                    onsubmit="return confirm('Remove this reserved ticket?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="text-sm font-medium text-red-600 hover:text-red-700">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <a href="{{ route('attendee.cart.index') }}"
                                class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-700 transition">
                                <span>Go To Cart</span>
                                <span>🛒</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Booking Modal -->
                <div x-show="showModal" x-cloak style="display:none;" @keydown.escape.window="showModal = false"
                    class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="fixed inset-0 bg-black/50"></div>

                    <!-- Modal box -->
                    <div class="relative w-full max-w-md mx-auto bg-white rounded-2xl shadow-xl overflow-hidden z-50">
                        <form action="{{ route('attendee.cart.store', $event) }}" method="POST" class="p-6">
                            @csrf

                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold">Reserve Tickets</h3>
                                <button type="button" class="text-slate-400 hover:text-slate-600"
                                    @click="showModal = false">✕</button>
                            </div>

                            <div class="mt-4 space-y-3">
                                <input type="hidden" name="ticket_category_id" :value="selected.id">

                                <div>
                                    <label class="text-sm text-slate-500">Category</label>
                                    <div class="mt-1 font-semibold text-slate-900" x-text="selected.name"></div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm text-slate-500">Price</label>
                                        <div class="mt-1 font-semibold text-slate-900">Rs <span
                                                x-text="Number(selected.price).toFixed(2)"></span></div>
                                    </div>

                                    <div>
                                        <label class="text-sm text-slate-500">Available</label>
                                        <div class="mt-1 font-semibold text-green-600" x-text="selected.available">
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm text-slate-500">Number of Tickets</label>
                                    <input type="number" name="quantity" x-model.number="qty" min="1"
                                        :max="selected.available" class="mt-1 w-full rounded-lg border px-3 py-2"
                                        @input="amount = (qty * selected.price).toFixed(2)">
                                </div>

                                <div>
                                    <label class="text-sm text-slate-500">Amount</label>
                                    <div class="mt-1 font-semibold text-slate-900">Rs <span x-text="amount"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <button type="submit"
                                    class="w-full rounded-lg bg-amber-500 px-4 py-2 text-white font-semibold hover:bg-amber-600">Reserve
                                    Tickets</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

        </div>

</x-app-layout>
