<x-app-layout>
    @php
        $glass = 'border border-white/60 bg-white/70 shadow-[0_8px_30px_rgba(15,23,42,0.06)] backdrop-blur-xl';
        $isCro = $isCro ?? false;
        $categoryClass = $categoryClass ?? \App\Enums\AttendeeNotificationCategory::class;

        $accents = [
            'assignment' => ['tile' => 'bg-sky-500/10 text-sky-600', 'bar' => 'from-sky-500 to-sky-300', 'pill' => 'bg-sky-600 text-white'],
            'ticket' => ['tile' => 'bg-indigo-500/10 text-indigo-600', 'bar' => 'from-indigo-500 to-indigo-300', 'pill' => 'bg-indigo-600 text-white'],
            'payment' => ['tile' => 'bg-emerald-500/10 text-emerald-600', 'bar' => 'from-emerald-500 to-emerald-300', 'pill' => 'bg-emerald-600 text-white'],
            'event' => ['tile' => 'bg-violet-500/10 text-violet-600', 'bar' => 'from-violet-500 to-violet-300', 'pill' => 'bg-violet-600 text-white'],
            'reminder' => ['tile' => 'bg-amber-500/10 text-amber-600', 'bar' => 'from-amber-500 to-amber-300', 'pill' => 'bg-amber-500 text-white'],
            'refund' => ['tile' => 'bg-teal-500/10 text-teal-600', 'bar' => 'from-teal-500 to-teal-300', 'pill' => 'bg-teal-600 text-white'],
            'interaction' => ['tile' => 'bg-rose-500/10 text-rose-600', 'bar' => 'from-rose-500 to-rose-300', 'pill' => 'bg-rose-600 text-white'],
            'wishlist' => ['tile' => 'bg-pink-500/10 text-pink-600', 'bar' => 'from-pink-500 to-pink-300', 'pill' => 'bg-pink-600 text-white'],
            'account' => ['tile' => 'bg-blue-500/10 text-blue-600', 'bar' => 'from-blue-500 to-blue-300', 'pill' => 'bg-blue-600 text-white'],
        ];

        $toneByType = [
            'event_assigned' => 'bg-sky-50 text-sky-700 ring-sky-100',
            'inquiry_submitted' => 'bg-rose-50 text-rose-700 ring-rose-100',
            'complaint_submitted' => 'bg-rose-50 text-rose-700 ring-rose-100',
            'refund_request_submitted' => 'bg-teal-50 text-teal-700 ring-teal-100',
            'event_starts_tomorrow' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'ticket_purchased' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'ticket_cancelled' => 'bg-rose-50 text-rose-700 ring-rose-100',
            'ticket_refunded' => 'bg-sky-50 text-sky-700 ring-sky-100',
            'payment_successful' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'payment_failed' => 'bg-rose-50 text-rose-700 ring-rose-100',
            'payment_pending' => 'bg-amber-50 text-amber-800 ring-amber-100',
            'ticket_expiry' => 'bg-orange-50 text-orange-700 ring-orange-100',
            'refund_request_received' => 'bg-sky-50 text-sky-700 ring-sky-100',
            'refund_approved' => 'bg-indigo-50 text-indigo-700 ring-indigo-100',
            'refund_rejected' => 'bg-rose-50 text-rose-700 ring-rose-100',
            'refund_completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'new_event' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'event_published' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'event_updated' => 'bg-sky-50 text-sky-700 ring-sky-100',
            'event_postponed' => 'bg-amber-50 text-amber-800 ring-amber-100',
            'event_rescheduled' => 'bg-indigo-50 text-indigo-700 ring-indigo-100',
            'event_schedule_announced' => 'bg-sky-50 text-sky-800 ring-sky-100',
            'event_cancelled' => 'bg-rose-50 text-rose-700 ring-rose-100',
            'event_completed' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'event_reminder' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'inquiry_replied' => 'bg-rose-50 text-rose-700 ring-rose-100',
            'complaint_replied' => 'bg-rose-50 text-rose-700 ring-rose-100',
            'saved_event_published' => 'bg-pink-50 text-pink-700 ring-pink-100',
            'ticket_sales_opened' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'email_verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'password_changed' => 'bg-blue-50 text-blue-700 ring-blue-100',
            'profile_updated' => 'bg-sky-50 text-sky-700 ring-sky-100',
        ];

        // Filters carried over when switching category (type is category-specific, so it resets).
        $carriedFilters = array_filter([
            'status' => $filters['status'] !== 'all' ? $filters['status'] : null,
            'range' => $filters['range'] !== 'all' ? $filters['range'] : null,
            'sort' => $filters['sort'] !== 'newest' ? $filters['sort'] : null,
            'q' => $filters['q'] !== '' ? $filters['q'] : null,
        ]);

        $carriedWithType = array_filter(array_merge($carriedFilters, ['type' => $filters['type']]));

        $selectClass = 'w-full rounded-xl border-white/70 bg-white/70 py-2.5 text-sm text-slate-700 shadow-sm backdrop-blur transition hover:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200';
        $categoryTabCols = $isCro
            ? 'sm:grid-cols-3 lg:grid-cols-6'
            : 'sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-9';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-3xl font-bold text-slate-900">Notifications</h2>
                    @if ($unreadCount > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-600/10 px-3 py-1 text-xs font-bold text-indigo-700 ring-1 ring-indigo-200">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-indigo-600"></span>
                            {{ $unreadCount }} unread
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-slate-500">
                    @if ($isCro)
                        Customer relations updates for your assigned events and support queue.
                    @else
                        All your EventHub updates, organized by category.
                    @endif
                </p>
            </div>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit"
                        class="group inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition duration-200 hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-xl hover:shadow-indigo-600/30">
                        <i class="bi bi-check2-all transition group-hover:scale-110"></i>
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="relative py-8">
        {{-- Decorative backdrop for the glass surfaces --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-24 left-1/4 h-72 w-72 rounded-full bg-indigo-300/30 blur-3xl"></div>
            <div class="absolute top-40 right-4 h-72 w-72 rounded-full bg-sky-300/25 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-violet-300/20 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/80 px-5 py-4 text-emerald-800 backdrop-blur">
                    <i class="bi bi-check-circle mr-1"></i>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Summary --}}
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ([
                    ['label' => 'Total', 'value' => $totalCount, 'icon' => 'bi-inbox', 'tile' => 'bg-slate-500/10 text-slate-600', 'status' => 'all'],
                    ['label' => 'Unread', 'value' => $unreadCount, 'icon' => 'bi-envelope-exclamation', 'tile' => 'bg-indigo-500/10 text-indigo-600', 'status' => 'unread'],
                    ['label' => 'Read', 'value' => $readCount, 'icon' => 'bi-envelope-open', 'tile' => 'bg-emerald-500/10 text-emerald-600', 'status' => 'read'],
                ] as $stat)
                    <a href="{{ route('notifications.index', array_filter(array_merge($carriedWithType, ['category' => $activeCategory?->value, 'status' => $stat['status'] === 'all' ? null : $stat['status']]))) }}"
                        @class([
                            'group flex items-center gap-3 rounded-2xl p-4 transition duration-200 hover:-translate-y-0.5 hover:bg-white/90 hover:shadow-[0_18px_40px_rgba(15,23,42,0.10)]',
                            $glass,
                            'ring-2 ring-indigo-300/70' => $filters['status'] === $stat['status'],
                        ])>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl text-lg transition group-hover:scale-105 {{ $stat['tile'] }}">
                            <i class="bi {{ $stat['icon'] }}"></i>
                        </span>
                        <span>
                            <span class="block text-2xl font-bold leading-none text-slate-900">{{ $stat['value'] }}</span>
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- Category tabs --}}
            <div class="rounded-2xl p-1.5 {{ $glass }}">
                <div class="flex gap-1 overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] sm:grid sm:overflow-visible {{ $categoryTabCols }} [&::-webkit-scrollbar]:hidden">
                    <a href="{{ route('notifications.index', $carriedFilters) }}"
                        @class([
                            'inline-flex shrink-0 items-center justify-center gap-2 rounded-xl px-3.5 py-2.5 text-xs font-semibold transition duration-200',
                            'bg-slate-900 text-white shadow-md' => ! $activeCategory,
                            'text-slate-600 hover:-translate-y-0.5 hover:bg-white hover:text-slate-900 hover:shadow-sm' => (bool) $activeCategory,
                        ])>
                        <i class="bi bi-collection"></i>
                        <span>All</span>
                    </a>
                    @foreach ($categories as $category)
                        @php
                            $unread = (int) ($categoryCounts[$category->value] ?? 0);
                            $isActive = $activeCategory?->value === $category->value;
                            $accent = $accents[$category->value];
                        @endphp
                        <a href="{{ route('notifications.index', array_merge($carriedFilters, ['category' => $category->value])) }}"
                            @class([
                                'inline-flex shrink-0 items-center justify-center gap-2 rounded-xl px-3.5 py-2.5 text-xs font-semibold transition duration-200',
                                $accent['pill'].' shadow-md' => $isActive,
                                'text-slate-600 hover:-translate-y-0.5 hover:bg-white hover:text-slate-900 hover:shadow-sm' => ! $isActive,
                            ])>
                            <i class="bi {{ $category->icon() }}"></i>
                            <span class="hidden sm:inline">{{ str_replace(' Notifications', '', $category->label()) }}</span>
                            <span class="sm:hidden">{{ ucfirst($category->value) }}</span>
                            @if ($unread > 0)
                                <span @class([
                                    'inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[10px] font-bold',
                                    'bg-white/25 text-white' => $isActive,
                                    'bg-indigo-100 text-indigo-700' => ! $isActive,
                                ])>{{ $unread }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('notifications.index') }}" class="rounded-3xl p-4 sm:p-5 {{ $glass }}">
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory->value }}">
                @endif

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <i class="bi bi-funnel text-indigo-600"></i>
                        Filter notifications
                    </p>
                    @if ($hasActiveFilters)
                        <a href="{{ route('notifications.index', array_filter(['category' => $activeCategory?->value])) }}"
                            class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-white hover:text-rose-600">
                            <i class="bi bi-x-circle"></i>
                            Clear filters
                        </a>
                    @endif
                </div>

                <div class="mt-4 grid gap-3 lg:grid-cols-12">
                    <div class="lg:col-span-5">
                        <label for="q" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                        <div class="relative">
                            <i class="bi bi-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input id="q" type="search" name="q" value="{{ $filters['q'] }}"
                                placeholder="Search notification text..."
                                class="w-full rounded-xl border-white/70 bg-white/70 py-2.5 pl-10 text-sm text-slate-700 shadow-sm backdrop-blur transition placeholder:text-slate-400 hover:bg-white focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200">
                        </div>
                    </div>

                    <div class="lg:col-span-3">
                        <label for="type" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Type</label>
                        <select id="type" name="type" onchange="this.form.submit()" class="{{ $selectClass }}">
                            <option value="">All types</option>
                            @foreach ($typeOptions as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}" @selected($filters['type'] === $typeValue)>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="status" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                        <select id="status" name="status" onchange="this.form.submit()" class="{{ $selectClass }}">
                            <option value="all" @selected($filters['status'] === 'all')>All</option>
                            <option value="unread" @selected($filters['status'] === 'unread')>Unread</option>
                            <option value="read" @selected($filters['status'] === 'read')>Read</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="range" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Period</label>
                        <select id="range" name="range" onchange="this.form.submit()" class="{{ $selectClass }}">
                            <option value="all" @selected($filters['range'] === 'all')>Any time</option>
                            <option value="today" @selected($filters['range'] === 'today')>Today</option>
                            <option value="week" @selected($filters['range'] === 'week')>Last 7 days</option>
                            <option value="month" @selected($filters['range'] === 'month')>Last 30 days</option>
                        </select>
                    </div>

                    <div class="lg:col-span-3">
                        <label for="sort" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sort</label>
                        <select id="sort" name="sort" onchange="this.form.submit()" class="{{ $selectClass }}">
                            <option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option>
                            <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option>
                        </select>
                    </div>

                    <div class="flex items-end lg:col-span-2">
                        <button type="submit"
                            class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition duration-200 hover:-translate-y-0.5 hover:bg-slate-800 hover:shadow-lg">
                            Apply
                        </button>
                    </div>
                </div>
            </form>

            {{-- Result meta --}}
            <div class="flex flex-wrap items-center justify-between gap-2 px-1">
                <p class="text-sm text-slate-500">
                    @if ($notifications->total() > 0)
                        Showing <span class="font-semibold text-slate-700">{{ $notifications->firstItem() }}–{{ $notifications->lastItem() }}</span>
                        of <span class="font-semibold text-slate-700">{{ $notifications->total() }}</span>
                        {{ Str::plural('notification', $notifications->total()) }}
                    @else
                        No matching notifications
                    @endif
                </p>
                @if ($activeCategory)
                    <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <i class="bi {{ $activeCategory->icon() }}"></i>
                        {{ $activeCategory->label() }}
                    </p>
                @endif
            </div>

            {{-- Notifications --}}
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $type = $data['type'] ?? null;
                    $category = $categoryClass::fromType($type);
                    $accent = $accents[$category->value] ?? $accents['event'];
                    $isUnread = is_null($notification->read_at);
                    $tone = $toneByType[$type] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
                @endphp
                <div @class([
                    'group relative overflow-hidden rounded-3xl transition duration-200 hover:-translate-y-0.5 hover:bg-white/90 hover:shadow-[0_20px_45px_rgba(15,23,42,0.12)]',
                    $glass,
                    'bg-indigo-50/50' => $isUnread,
                ])>
                    <span class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b {{ $accent['bar'] }} {{ $isUnread ? 'opacity-100' : 'opacity-0 transition group-hover:opacity-60' }}"></span>

                    <div class="flex flex-col gap-4 p-5 pl-6 sm:flex-row sm:items-start sm:justify-between sm:p-6 sm:pl-7">
                        <div class="flex min-w-0 flex-1 gap-4">
                            <span class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-lg transition group-hover:scale-105 sm:inline-flex {{ $accent['tile'] }}">
                                <i class="bi {{ $category->icon() }}"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="mb-2 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-white/80 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200">
                                        <i class="bi {{ $category->icon() }} text-[10px]"></i>
                                        {{ str_replace(' Notifications', '', $category->label()) }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $tone }}">
                                        {{ $categoryClass::labelForType($type) }}
                                    </span>
                                    @if ($isUnread)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wide text-indigo-600">
                                            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                            New
                                        </span>
                                    @endif
                                </div>
                                <p class="text-base font-semibold text-slate-900">{{ $data['message'] ?? 'Notification' }}</p>
                                <p class="mt-1 inline-flex items-center gap-1.5 text-sm text-slate-500">
                                    <i class="bi bi-clock text-xs"></i>
                                    <span title="{{ $notification->created_at->format('d M Y, g:i A') }}">{{ $notification->created_at->diffForHumans() }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            @if ($isUnread)
                                <a href="{{ route('notifications.read', $notification->id) }}"
                                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-indigo-600/20 transition duration-200 hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-lg">
                                    <i class="bi bi-box-arrow-up-right text-xs"></i>
                                    View &amp; mark read
                                </a>
                            @else
                                <a href="{{ $data['url'] ?? '#' }}"
                                    class="inline-flex items-center gap-2 rounded-xl bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-md">
                                    <i class="bi bi-box-arrow-up-right text-xs"></i>
                                    View
                                </a>
                                <form method="POST" action="{{ route('notifications.unread', $notification->id) }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:text-indigo-700 hover:shadow-md">
                                        <i class="bi bi-envelope text-xs"></i>
                                        Mark unread
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300/80 bg-white/60 p-12 text-center backdrop-blur-xl">
                    <span class="mx-auto mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-500/10 text-2xl text-slate-400">
                        <i class="bi bi-bell-slash"></i>
                    </span>
                    <p class="font-semibold text-slate-700">
                        @if ($hasActiveFilters)
                            No notifications match your filters
                        @elseif ($activeCategory)
                            No {{ strtolower(str_replace(' Notifications', '', $activeCategory->label())) }} notifications yet
                        @else
                            No notifications yet
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        @if ($hasActiveFilters)
                            Try a different type, status, or period.
                        @elseif ($isCro)
                            Assignment, inquiry, complaint, refund, and event updates will appear here.
                        @else
                            Updates about your tickets, payments, and saved events will appear here.
                        @endif
                    </p>
                    @if ($hasActiveFilters)
                        <a href="{{ route('notifications.index', array_filter(['category' => $activeCategory?->value])) }}"
                            class="mt-5 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-slate-800">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Reset filters
                        </a>
                    @endif
                </div>
            @endforelse

            @if ($notifications->hasPages())
                <div class="rounded-2xl px-4 py-3 {{ $glass }}">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
