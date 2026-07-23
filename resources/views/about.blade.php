<x-public-layout :title="t(['en' => 'About', 'si' => 'අපි ගැන'])">
    {{-- Hero --}}
    <section class="relative mx-auto max-w-7xl px-4 pt-14 pb-16 sm:px-6 sm:pt-20 sm:pb-24 lg:px-8">
        <div class="mx-auto max-w-3xl text-center animate-[fadeInUp_0.7s_ease-out]">
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-primary">{{ t(['en' => 'About us', 'si' => 'අපි ගැන']) }}</p>
            <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl dark:text-white">
                {{ config('app.name', 'EventHub') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600 dark:text-slate-300">
                {{ t(['en' => 'We make discovering and booking live experiences simple — so you can spend less time searching and more time showing up.', 'si' => 'අපි සජීවී අත්දැකීම් සොයාගැනීම සහ වෙන්කරවා ගැනීම පහසු කරනවා — එවිට ඔබට සෙවීම සඳහා කාලය අඩු කර වැඩි කාලය සජීවීව සහභාගී වීමට වැය කළ හැක.']) }}
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('welcome') }}#events"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-primary/30 transition hover:bg-primary-dark">
                    <i class="bi bi-search"></i>
                    {{ t(['en' => 'Browse Events', 'si' => 'ප්‍රසංග සොයන්න']) }}
                </a>
                @guest
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">
                        {{ t(['en' => 'Create Free Account', 'si' => 'නොමිලේ ගිණුමක් සාදන්න']) }}
                    </a>
                @endguest
            </div>
        </div>
    </section>

    {{-- Mission --}}
    <section class="border-y border-slate-200/80 bg-white/60 py-16 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/40">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div class="animate-[fadeInUp_0.8s_ease-out]">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">{{ t(['en' => 'Our mission', 'si' => 'අපගේ මෙහෙවර']) }}</p>
                    <h2 class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">
                        {{ t(['en' => 'Connecting people with moments that matter', 'si' => 'වැදගත් අවස්ථා සමඟ මිනිසුන් සම්බන්ධ කිරීම']) }}
                    </h2>
                    <p class="mt-5 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                        {{ t(['en' => 'EventHub brings organizers and attendees together on one platform. Whether it’s a concert, workshop, meetup, or festival, we help hosts publish with confidence and guests book with ease.', 'si' => 'EventHub සංවිධායකයන් සහ සහභාගීවන්නන් එක වේදිකාවකට ගෙන එයි. එය සංගීත ප්‍රසංගයක්, වැඩමුළුවක්, හමුවක් හෝ උත්සවයක් වේවා, සත්කාරකයන්ට විශ්වාසයෙන් ප්‍රකාශයට පත් කිරීමටත් අමුත්තන්ට පහසුවෙන් වෙන්කරවා ගැනීමටත් අපි උදව් කරමු.']) }}
                    </p>
                    <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                        {{ t(['en' => 'From discovery to checkout, every step is designed to be clear, fast, and secure — so the focus stays on the event itself.', 'si' => 'සොයා ගැනීමේ සිට ගෙවීම දක්වා සෑම පියවරක්ම පැහැදිලි, වේගවත් සහ ආරක්ෂිත වන පරිදි නිර්මාණය කර ඇත — අවධානය ප්‍රසංග මතම තබා ගැනීමට.']) }}
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 animate-[fadeInUp_0.9s_ease-out]">
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <i class="bi bi-people text-xl"></i>
                        </span>
                        <h3 class="mt-4 font-semibold text-slate-900 dark:text-white">{{ t(['en' => 'For attendees', 'si' => 'සහභාගීවන්නන් සඳහා']) }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                            {{ t(['en' => 'Find events by category, save favorites, and book tickets in seconds.', 'si' => 'වර්ග අනුව  සොයන්න, ප්‍රියතමයන් සුරකින්න, තත්පර කිහිපයකින් ටිකට් වෙන්කරවා ගන්න.']) }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-slate-700 dark:bg-slate-900 sm:mt-6">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600">
                            <i class="bi bi-mic text-xl"></i>
                        </span>
                        <h3 class="mt-4 font-semibold text-slate-900 dark:text-white">{{ t(['en' => 'For organizers', 'si' => 'සංවිධායකයන් සඳහා']) }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                            {{ t(['en' => 'Publish events, manage bookings, and reach the right audience.', 'si' => 'ප්‍රසංග ප්‍රකාශයට පත් කරන්න, වෙන්කිරීම් කළමනාකරණය කරන්න, නිවැරදි ප්‍රේක්ෂකයන් වෙත ළඟා වන්න.']) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">{{ t(['en' => 'How it works', 'si' => 'එය ක්‍රියා කරන ආකාරය']) }}</p>
            <h2 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ t(['en' => 'Three steps to your next event', 'si' => 'ඔබේ ඊළඟ ප්‍රසංගයට පියවර තුනක්']) }}</h2>
        </div>

        <ol class="mt-12 grid gap-8 sm:grid-cols-3">
            <li class="relative text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-xl font-bold text-white shadow-lg shadow-primary/25">1</span>
                <h3 class="mt-5 text-lg font-semibold text-slate-900 dark:text-white">{{ t(['en' => 'Discover', 'si' => 'සොයා ගන්න']) }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ t(['en' => 'Browse upcoming events and filter by the categories you care about.', 'si' => 'ඉදිරි ප්‍රසංග බලන්න සහ ඔබ කැමති කාන්ඩය අනුව සොයන්න.']) }}
                </p>
            </li>
            <li class="relative text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-xl font-bold text-white shadow-lg shadow-primary/25">2</span>
                <h3 class="mt-5 text-lg font-semibold text-slate-900 dark:text-white">{{ t(['en' => 'Book', 'si' => 'වෙන්කරවා ගන්න']) }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ t(['en' => 'Choose your tickets and complete a secure checkout when you’re ready.', 'si' => 'ඔබේ ටිකට් තෝරා, සූදානම් වූ විට ආරක්ෂිත ගෙවීම සම්පූර්ණ කරන්න.']) }}
                </p>
            </li>
            <li class="relative text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-xl font-bold text-white shadow-lg shadow-primary/25">3</span>
                <h3 class="mt-5 text-lg font-semibold text-slate-900 dark:text-white">{{ t(['en' => 'Enjoy', 'si' => 'රස විඳින්න']) }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ t(['en' => 'Get updates, manage your bookings, and show up ready for the experience.', 'si' => 'යාවත්කාලීන ලබා ගන්න, වෙන්කිරීම් කළමනාකරණය කරන්න, ප්‍රසංගට සූදානම්ව පැමිණෙන්න.']) }}
                </p>
            </li>
        </ol>
    </section>

    {{-- What we stand for --}}
    <section class="border-y border-slate-200/80 bg-white/60 py-16 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/40">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">{{ t(['en' => 'What we value', 'si' => 'අප අගය කරන දේ']) }}</p>
                <h2 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ t(['en' => 'Built for clarity and trust', 'si' => 'පැහැදිලිකම සහ විශ්වාසය සඳහා නිර්මාණය කළා']) }}</h2>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div class="p-1">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <i class="bi bi-shield-check text-xl"></i>
                    </span>
                    <h3 class="mt-4 font-semibold text-slate-900 dark:text-white">{{ t(['en' => 'Secure booking', 'si' => 'ආරක්ෂිත වෙන්කිරීම']) }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ t(['en' => 'Checkout and account flows are designed to keep your details protected.', 'si' => 'ගෙවීම් සහ ගිණුම් ක්‍රියාවලි ඔබේ විස්තර ආරක්ෂා කිරීමට නිර්මාණය කර ඇත.']) }}
                    </p>
                </div>
                <div class="p-1">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
                        <i class="bi bi-lightning-charge text-xl"></i>
                    </span>
                    <h3 class="mt-4 font-semibold text-slate-900 dark:text-white">{{ t(['en' => 'Fast discovery', 'si' => 'වේගවත් සොයාගැනීම']) }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ t(['en' => 'Find what’s happening near you without wading through clutter.', 'si' => 'අනවශ්‍ය දේ අතරින් ගමන් නොකර ඔබ අවට සිදුවන දේ සොයා ගන්න.']) }}
                    </p>
                </div>
                <div class="p-1">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600">
                        <i class="bi bi-heart text-xl"></i>
                    </span>
                    <h3 class="mt-4 font-semibold text-slate-900 dark:text-white">{{ t(['en' => 'Personal picks', 'si' => 'පුද්ගලික තේරීම්']) }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        {{ t(['en' => 'Like and save events so your next plans stay in one place.', 'si' => 'කැමති ප්‍රසංග සුරකින්න — ඔබේ ඊළඟ සැලසුම් එක තැනක තබා ගන්න.']) }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-r from-slate-900 via-slate-800 to-primary px-6 py-12 text-center text-white shadow-2xl sm:px-12 sm:py-14">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.12),_transparent_50%)]"></div>
            <div class="relative">
                <h2 class="text-2xl font-bold sm:text-3xl">{{ t(['en' => 'Ready to explore what’s next?', 'si' => 'ඊළඟ දේ ගවේෂණය කිරීමට සූදානම්ද?']) }}</h2>
                <p class="mx-auto mt-3 max-w-lg text-slate-200">
                    {{ t(['en' => 'Jump into upcoming events, or create an account to book, save, and stay updated.', 'si' => 'ඉදිරි ප්‍රසංග වෙත පිවිසෙන්න, නැතහොත් වෙන්කරවා ගැනීමට, සුරැකීමට සහ යාවත්කාලීනව සිටීමට ගිණුමක් සාදන්න.']) }}
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('welcome') }}#events"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-6 py-3 text-sm font-semibold text-slate-900 shadow-lg transition hover:bg-slate-100">
                        <i class="bi bi-calendar-event"></i>
                        {{ t(['en' => 'View Events', 'si' => 'ප්‍රසංග බලන්න']) }}
                    </a>
                    @guest
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/20">
                            {{ t(['en' => 'Get Started Free', 'si' => 'නොමිලේ ආරම්භ කරන්න']) }}
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/20">
                            {{ t(['en' => 'Go to Dashboard', 'si' => 'විස්තර පුවරුවට යන්න']) }}
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
