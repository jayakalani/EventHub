<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">Notifications</h2>
                <p class="mt-1 text-slate-500">Stay updated on events you care about.</p>
            </div>
            @if(Auth::user()->unreadNotifications->count() > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = is_null($notification->read_at);
                @endphp
                <div class="rounded-3xl border {{ $isUnread ? 'border-indigo-200 bg-indigo-50/40' : 'border-slate-200 bg-white' }} shadow-sm overflow-hidden">
                    <div class="p-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                @if($data['type'] === 'new_event')
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">New event</span>
                                @elseif($data['type'] === 'event_reminder')
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Event reminder</span>
                                @elseif($data['type'] === 'ticket_expiry')
                                    <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-semibold text-orange-700">Reservation expiring</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-700">Event updated</span>
                                @endif
                                @if($isUnread)
                                    <span class="inline-flex h-2 w-2 rounded-full bg-indigo-500"></span>
                                @endif
                            </div>
                            <p class="text-base font-semibold text-slate-900">{{ $data['message'] ?? 'Notification' }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            @if($isUnread)
                                <a href="{{ route('notifications.read', $notification->id) }}"
                                    class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                                    View & mark read
                                </a>
                            @else
                                <a href="{{ $data['url'] ?? '#' }}"
                                    class="inline-flex items-center rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition">
                                    View
                                </a>
                                <form method="POST" action="{{ route('notifications.unread', $notification->id) }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                                        Mark unread
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <p class="text-slate-500">No notifications yet.</p>
                </div>
            @endforelse

            @if($notifications->hasPages())
                <div class="pt-4">
                    {{ $notifications->links() }}
                </div>
            @endif

        </div>
    </div>

</x-app-layout>
