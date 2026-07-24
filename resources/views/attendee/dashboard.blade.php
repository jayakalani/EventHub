<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                        {{ t(['en' => 'Attendee Dashboard', 'si' => 'ප්‍රේක්ෂක විස්තර පුවරුව']) }}
                    </p>
                </div>

                <h1 class="mt-1.5 truncate text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
                    {{ t(['en' => 'Welcome back,', 'si' => 'නැවත සාදරයෙන් පිළිගනිමු,']) }}
                    <span class="text-primary">{{ Auth::user()->first_name }}</span>
                </h1>

                <p class="mt-1 max-w-xl text-sm leading-relaxed text-slate-600">
                    {{ t(['en' => 'Discover upcoming events and book your next experience.', 'si' => 'ඉදිරි ප්‍රසංග සොයා ගෙන ඔබේ ඊළඟ ප්‍රසංගය වෙන්කරවා ගන්න.']) }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">

                <div class="inline-flex items-center gap-2.5 rounded-2xl border border-slate-200 bg-white px-3.5 py-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-primary text-white shadow-sm shadow-primary/25">
                        <i class="bi bi-calendar2-event text-sm" aria-hidden="true"></i>
                    </span>
                    <div class="leading-tight">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            {{ t(['en' => 'Events', 'si' => 'ප්‍රසංග']) }}
                        </p>
                        <p class="text-sm font-semibold tabular-nums text-slate-900">{{ $events->count() }}</p>
                    </div>
                </div>

                <a href="{{ route('attendee.bookings.index') }}"
                    class="inline-flex items-center gap-2.5 rounded-2xl border border-slate-200 bg-white px-3.5 py-2 transition hover:border-emerald-300 hover:bg-emerald-50">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm shadow-emerald-600/25">
                        <i class="bi bi-ticket-perforated text-sm" aria-hidden="true"></i>
                    </span>
                    <div class="leading-tight">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            {{ t(['en' => 'Bookings', 'si' => 'වෙන්කිරීම්']) }}
                        </p>
                        <p class="text-sm font-semibold tabular-nums text-slate-900">{{ $myBookings ?? 0 }}</p>
                    </div>
                </a>

                <div x-data="{ open: false }" class="relative">
                    <button type="button"
                        @click="open = !open"
                        :aria-expanded="open.toString()"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-primary/30 hover:bg-primary/5">
                        <i class="bi bi-sliders2 text-slate-500" aria-hidden="true"></i>
                        <span class="hidden sm:inline">{{ t(['en' => 'Settings', 'si' => 'සැකසුම්']) }}</span>
                        <svg class="h-4 w-4 text-slate-500 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open"
                        x-cloak
                        style="display: none;"
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10 ring-1 ring-black/5">

                        <div class="border-b border-slate-100 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                {{ t(['en' => 'Quick overview', 'si' => 'ක්ෂණික දළ විශ්ලේෂණය']) }}
                            </p>
                        </div>

                        <div class="space-y-3 p-4">
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-medium text-slate-600">{{ t(['en' => 'Available Events', 'si' => 'ලබා ගත හැකි ප්‍රසංග']) }}</p>
                                        <h3 class="mt-1 text-2xl font-semibold tracking-tight text-primary">{{ $events->count() }}</h3>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ t(['en' => 'Currently active events', 'si' => 'දැනට සක්‍රීය ප්‍රසංග']) }}</p>
                                    </div>
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                        <i class="bi bi-calendar2-event" aria-hidden="true"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-medium text-slate-600">{{ t(['en' => 'My Bookings', 'si' => 'මගේ වෙන්කිරීම්']) }}</p>
                                        <h3 class="mt-1 text-2xl font-semibold tracking-tight text-emerald-600">{{ $myBookings ?? 0 }}</h3>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ t(['en' => 'Tickets booked', 'si' => 'වෙන්කර ගත් ටිකට්']) }}</p>
                                    </div>
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                        <i class="bi bi-ticket-perforated" aria-hidden="true"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </x-slot>

    <div class="relative overflow-hidden bg-gradient-to-b from-slate-50 via-white to-blue-50/40">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-[radial-gradient(ellipse_at_top,_rgba(37,99,235,0.08),_transparent_60%)]" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">

            @if (! empty($selectedHost))
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-primary/20 bg-primary/5 px-4 py-3 shadow-sm shadow-primary/10 backdrop-blur-sm">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary text-white">
                            <i class="bi bi-funnel" aria-hidden="true"></i>
                        </span>
                        <p class="text-sm text-slate-800">
                            {{ t(['en' => 'Showing events hosted by', 'si' => ' මෙම සත්කාරකයාගේ ප්‍රසංග']) }}
                            <span class="font-semibold">{{ $selectedHost->name }}</span>
                        </p>
                    </div>
                    <a href="{{ route('attendee.dashboard') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-1.5 text-sm font-semibold text-primary-dark shadow-sm ring-1 ring-primary/20 transition hover:bg-primary/5">
                        <i class="bi bi-x-lg text-xs" aria-hidden="true"></i>
                        {{ t(['en' => 'Clear filter', 'si' => 'තේරීම ඉවත් කරන්න']) }}
                    </a>
                </div>
            @endif

            {{-- Search toolbar --}}
            
            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm shadow-slate-200/50">
                <div
                    class="relative flex flex-col gap-4 overflow-hidden border-b border-[#0F0363]/50 px-5 py-4 text-white sm:flex-row sm:items-center sm:justify-between sm:px-6"
                    style="background: linear-gradient(115deg, #02031F 0%, #030638 25%, #070130 50%, #0F0363 75%, #2A1585 100%);">
                    <div
                        class="pointer-events-none absolute inset-0"
                        style="background:
                            radial-gradient(ellipse 90% 70% at 100% -10%, rgba(42, 21, 133, 0.45) 0%, transparent 55%),
                            radial-gradient(ellipse 70% 60% at 0% 110%, rgba(2, 3, 31, 0.75) 0%, transparent 50%),
                            linear-gradient(160deg, transparent 25%, rgba(15, 3, 99, 0.35) 55%, transparent 80%);"
                        aria-hidden="true"></div>
                    <div class="pointer-events-none absolute -right-10 top-0 h-40 w-40 rounded-full bg-[#2A1585]/50 blur-3xl" aria-hidden="true"></div>
                    <div class="pointer-events-none absolute -left-8 bottom-0 h-32 w-32 rounded-full bg-[#02031F]/80 blur-2xl" aria-hidden="true"></div>
                    <div class="relative min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-violet-200/90">
                            {{ t(['en' => 'Explore', 'si' => 'ගවේෂණය']) }}
                        </p>
                        <h2 class="mt-0.5 text-lg font-semibold tracking-tight sm:text-xl">
                            {{ t(['en' => 'Discover Amazing Events', 'si' => 'විශිෂ්ට ප්‍රසංග සොයන්න']) }}
                        </h2>

                        <p class="mt-0.5 text-sm text-violet-100/90">
                            {{ t(['en' => 'Explore concerts, workshops, conferences, sports events and more.', 'si' => 'සංගීත ප්‍රසංග, වැඩමුළු, සම්මන්ත්‍රණ, ක්‍රීඩා ප්‍රසංග සහ තවත් බොහෝ දේ ගවේෂණය කරන්න.']) }}
                        </p>
                    </div>

                    <form action="{{ route('attendee.dashboard') }}" method="GET" class="relative w-full lg:max-w-xl lg:shrink-0">

                        <div class="grid gap-2 sm:grid-cols-[1fr_auto_auto]">

                            @if (request('host'))
                                <input type="hidden" name="host" value="{{ request('host') }}">
                            @endif

                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="{{ t(['en' => 'Event Name', 'si' => 'ප්‍රසංගයේ නම']) }}" class="rounded-xl border-0 px-3.5 py-2 text-sm text-slate-800 shadow">

                            <input type="date" name="date" value="{{ request('date') }}"
                                class="rounded-xl border-0 px-3.5 py-2 text-sm text-slate-800 shadow">

                            <button type="submit"
                                class="rounded-xl bg-white px-5 py-2 text-sm font-semibold text-[#0F0363] shadow hover:bg-violet-50 transition">
                                {{ t(['en' => 'Search', 'si' => 'සොයන්න']) }}
                            </button>

                        </div>

                    </form>
                </div>
            </section>

            
            
            {{-- Browse events --}}
            <section>
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">
                            {{ t(['en' => 'Browse', 'si' => 'බ්‍රවුස්']) }}
                        </p>
                        <h2 class="mt-0.5 text-lg font-semibold tracking-tight text-slate-900 sm:text-xl">
                            {{ t(['en' => 'Upcoming events', 'si' => 'ඉදිරි ප්‍රසංග']) }}
                        </h2>
                    </div>
                    <p class="text-sm text-slate-600">
                        {{ t(['en' => 'Filter by category, then open an event to book.', 'si' => 'වර්ගය අනුව පෙරහන් කර වෙන්කිරීමට ප්‍රසංගයක් විවෘත කරන්න.']) }}
                    </p>
                </div>

                @include('partials.events-browse', ['compact' => true])
            </section>

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
                    <a href="{{ route('attendee.support.index', ['tab' => 'complaints']) }}"
                        class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-primary shadow-sm transition hover:border-primary/30 hover:bg-primary/5 sm:self-auto">
                        {{ t(['en' => 'View my complaints', 'si' => 'මගේ පැමිණිලි බලන්න']) }}
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="p-5 sm:p-6">
                    @if(session('success'))
                        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            {{ session('success') }}
                        </div>
                    @endif

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

            @if ($pastEvents->isNotEmpty())
                <section class="pt-2">
                    <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                                {{ t(['en' => 'History', 'si' => 'ඉතිහාසය']) }}
                            </p>
                            <h2 class="mt-0.5 text-lg font-semibold tracking-tight text-slate-900 sm:text-xl">
                                {{ t(['en' => 'Past Events', 'si' => 'අතීත ප්‍රසංග']) }}
                            </h2>
                        </div>
                        <p class="text-sm text-slate-600">
                            {{ t(['en' => 'Completed events you can still revisit for details, tickets, and feedback.', 'si' => 'අවසන් වූ ප්‍රසංග විස්තර, ටිකට් සහ ප්‍රතිචාර නැවත බැලිය හැකිය.']) }}
                        </p>
                    </div>

                    @include('partials.events-browse', [
                        'events' => $pastEvents,
                        'browseSection' => 'past',
                        'compact' => true,
                    ])
                </section>
            @endif

        </div>
    </div>

</x-app-layout>
