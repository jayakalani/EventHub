<x-public-layout :title="t(['en' => 'Help / FAQ', 'si' => 'උදව් / නිති ප්‍රශ්න'])">
    <x-slot:head>
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </x-slot:head>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8"
        x-data="{
            open: @js(
                old('_open', (session('status') === 'help-contact-sent' || $errors->any()) ? 'contact' : null)
            ),
            init() {
                if (window.location.hash === '#help-contact' || window.location.hash === '#contact') {
                    this.open = 'contact';
                }
            }
        }">

        <div class="mx-auto max-w-3xl text-center">
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                {{ t(['en' => 'Help / FAQ', 'si' => 'උදව් / නිති ප්‍රශ්න']) }}
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ t([
                    'en' => 'Find answers about tickets, bookings, refunds, your account, and more — or contact our help team below.',
                    'si' => 'ටිකට්, වෙන්කිරීම්, ආපසු ගෙවීම්, ඔබේ ගිණුම සහ තවත් දේ ගැන පිළිතුරු සොයන්න — නැතහොත් පහතින් අපගේ උදව් කණ්ඩායම අමතන්න.',
                ]) }}
            </p>
        </div>

        {{-- Categories --}}
        <section class="mt-10 sm:mt-12">
            <h2 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                {{ t(['en' => 'Categories', 'si' => 'කාණ්ඩ']) }}
            </h2>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $categories = [
                        [
                            'id' => 'buy-tickets',
                            'icon' => 'bi-ticket-perforated',
                            'title' => t(['en' => 'Buy Tickets', 'si' => 'ටිකට් මිලදී ගන්න']),
                            'desc' => t(['en' => 'Browsing events, cart, checkout and payments', 'si' => 'ප්‍රසංග බැලීම, කරත්තය, ගෙවීම්']),
                        ],
                        [
                            'id' => 'my-tickets',
                            'icon' => 'bi-journal-check',
                            'title' => t(['en' => 'My Tickets', 'si' => 'මගේ ටිකට්']),
                            'desc' => t(['en' => 'How to manage your bookings and tickets', 'si' => 'ඔබේ වෙන්කිරීම් සහ ටිකට් කළමනාකරණය']),
                        ],
                        [
                            'id' => 'ticket-delivery',
                            'icon' => 'bi-envelope-open',
                            'title' => t(['en' => 'Ticket Delivery', 'si' => 'ටිකට් ලබාදීම']),
                            'desc' => t(['en' => 'Email confirmations and PDF tickets', 'si' => 'ඊමේල් තහවුරු කිරීම් සහ PDF ටිකට්']),
                        ],
                        [
                            'id' => 'wallet',
                            'icon' => 'bi-wallet2',
                            'title' => t(['en' => 'Wallet & Payments', 'si' => 'පසුම්බිය සහ ගෙවීම්']),
                            'desc' => t(['en' => 'Top-ups, wallet balance and checkout', 'si' => 'පසුම්බි ආරෝපණ, ශේෂය සහ ගෙවීම']),
                        ],
                        [
                            'id' => 'refunds',
                            'icon' => 'bi-cash-stack',
                            'title' => t(['en' => 'Refunds', 'si' => 'ආපසු ගෙවීම්']),
                            'desc' => t(['en' => 'Details on refund requests and reviews', 'si' => 'ආපසු ගෙවීම් ඉල්ලීම් සහ සමාලෝචන']),
                        ],
                        [
                            'id' => 'my-account',
                            'icon' => 'bi-person',
                            'title' => t(['en' => 'My Account', 'si' => 'මගේ ගිණුම']),
                            'desc' => t(['en' => 'Profile, security and login help', 'si' => 'පැතිකඩ, ආරක්ෂාව සහ පිවිසුම් උදව්']),
                        ],
                        [
                            'id' => 'event-updates',
                            'icon' => 'bi-bell',
                            'title' => t(['en' => 'Event Updates', 'si' => 'ප්‍රසංග යාවත්කාලීන']),
                            'desc' => t(['en' => 'Reminders, cancellations and venue info', 'si' => 'මතක් කිරීම්, අවලංගු කිරීම් සහ ස්ථාන තොරතුරු']),
                        ],
                        [
                            'id' => 'support',
                            'icon' => 'bi-headset',
                            'title' => t(['en' => 'Support Center', 'si' => 'සහාය මධ්‍යස්ථානය']),
                            'desc' => t(['en' => 'Inquiries and complaints in your account', 'si' => 'ඔබේ ගිණුමේ විමසුම් සහ පැමිණිලි']),
                        ],
                        [
                            'id' => 'contact',
                            'icon' => 'bi-chat-left-quote',
                            'title' => t(['en' => 'Contact Us', 'si' => 'අප අමතන්න']),
                            'desc' => t(['en' => 'Find support', 'si' => 'සහාය සොයන්න']),
                        ],
                    ];
                @endphp

                @foreach ($categories as $category)
                    <button type="button"
                        @click="open = open === '{{ $category['id'] }}' ? null : '{{ $category['id'] }}'; $nextTick(() => { if (open === '{{ $category['id'] }}') document.getElementById('help-{{ $category['id'] }}')?.scrollIntoView({ behavior: 'smooth', block: 'start' }) })"
                        class="group flex w-full items-center gap-4 rounded-2xl border bg-white px-4 py-4 text-left shadow-sm transition hover:border-primary/30 hover:shadow-md dark:bg-slate-900/80 dark:hover:border-primary/40"
                        :class="open === '{{ $category['id'] }}'
                            ? 'border-primary/40 ring-2 ring-primary/15'
                            : 'border-slate-200/90 dark:border-slate-700/80'">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <i class="bi {{ $category['icon'] }} text-xl"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ $category['title'] }}</span>
                            <span class="mt-0.5 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $category['desc'] }}</span>
                        </span>
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-400 transition group-hover:border-primary/30 group-hover:text-primary dark:border-slate-600"
                            :class="open === '{{ $category['id'] }}' ? 'border-primary/40 bg-primary/10 text-primary' : ''">
                            <i class="bi bi-chevron-right text-sm transition" :class="open === '{{ $category['id'] }}' ? 'rotate-90' : ''"></i>
                        </span>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- Category details --}}
        <section class="mt-8 space-y-4">
            <div id="help-buy-tickets" x-show="open === 'buy-tickets'" x-cloak
                class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900/80">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => 'Buy Tickets', 'si' => 'ටිකට් මිලදී ගන්න']) }}</h3>
                <ul class="mt-3 space-y-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <li>• {{ t(['en' => 'Browse published events from the home page or your attendee dashboard.', 'si' => 'මුල් පිටුවෙන් හෝ ඔබේ සහභාගී විස්තර පුවරුවෙන් ප්‍රකාශිත ප්‍රසංග බලන්න.']) }}</li>
                    <li>• {{ t(['en' => 'Choose a ticket category, add tickets to your cart, then complete checkout with card or wallet balance.', 'si' => 'ටිකට් කාණ්ඩයක් තෝරා කරත්තයට එකතු කර, කාඩ්පතින් හෝ පසුම්බි ශේෂයෙන් ගෙවන්න.']) }}</li>
                    <li>• {{ t(['en' => 'Cart items may expire after a short hold period — finish checkout before they are released.', 'si' => 'කරත්ත අයිතම කෙටි කාලයකට පසු කල් ඉකුත් විය හැක — නිදහස් වීමට පෙර ගෙවීම අවසන් කරන්න.']) }}</li>
                </ul>
            </div>

            <div id="help-my-tickets" x-show="open === 'my-tickets'" x-cloak
                class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900/80">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => 'My Tickets', 'si' => 'මගේ ටිකට්']) }}</h3>
                <ul class="mt-3 space-y-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <li>• {{ t(['en' => 'After purchase, open Tickets in your attendee menu to view bookings.', 'si' => 'මිලදී ගැනීමෙන් පසු, වෙන්කිරීම් බැලීමට සහභාගී මෙනුවේ ටිකට් විවෘත කරන්න.']) }}</li>
                    <li>• {{ t(['en' => 'You can download ticket PDFs and check event date, venue, and category details there.', 'si' => 'එහිදී PDF ටිකට් බාගත කර දිනය, ස්ථානය සහ කාණ්ඩ විස්තර පරීක්ෂා කළ හැක.']) }}</li>
                    <li>• {{ t(['en' => 'Tickets on EventHub are issued for your account and are non-transferable.', 'si' => 'EventHub ටිකට් ඔබේ ගිණුමට නිකුත් කෙරෙන අතර මාරු කළ නොහැක.']) }}</li>
                </ul>
            </div>

            <div id="help-ticket-delivery" x-show="open === 'ticket-delivery'" x-cloak
                class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900/80">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => 'Ticket Delivery', 'si' => 'ටිකට් ලබාදීම']) }}</h3>
                <ul class="mt-3 space-y-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <li>• {{ t(['en' => 'Successful payments send a confirmation email with your booking details.', 'si' => 'සාර්ථක ගෙවීම් සමඟ ඔබේ වෙන්කිරීම් විස්තර සහිත තහවුරු ඊමේල් යවනු ලැබේ.']) }}</li>
                    <li>• {{ t(['en' => 'You can also download tickets anytime from My Tickets in your account.', 'si' => 'ඔබේ ගිණුමේ මගේ ටිකට් වෙතින් ඕනෑම වේලාවක ටිකට් බාගත කළ හැක.']) }}</li>
                    <li>• {{ t(['en' => 'If an email is missing, check spam folders first, then contact us with your booking ID.', 'si' => 'ඊමේල් නොමැති නම් පළමුව spam පරීක්ෂා කර, පසුව ඔබේ වෙන්කිරීම් අංකය සමඟ අප අමතන්න.']) }}</li>
                </ul>
            </div>

            <div id="help-wallet" x-show="open === 'wallet'" x-cloak
                class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900/80">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => 'Wallet & Payments', 'si' => 'පසුම්බිය සහ ගෙවීම්']) }}</h3>
                <ul class="mt-3 space-y-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <li>• {{ t(['en' => 'Use Wallet to top up balance and pay for tickets faster at checkout.', 'si' => 'ගෙවීමේදී වේගයෙන් ටිකට් ගෙවීමට පසුම්බිය භාවිතා කර ශේෂය ආරෝපණය කරන්න.']) }}</li>
                    <li>• {{ t(['en' => 'Card payments are processed securely through Stripe Checkout.', 'si' => 'කාඩ්පත් ගෙවීම් Stripe Checkout හරහා ආරක්ෂිතව සැකසේ.']) }}</li>
                    <li>• {{ t(['en' => 'Approved refunds may be returned according to the original payment method and platform policy.', 'si' => 'අනුමත ආපසු ගෙවීම් මුල් ගෙවීම් ක්‍රමය සහ වේදිකා ප්‍රතිපත්තිය අනුව ආපසු ලැබිය හැක.']) }}</li>
                </ul>
            </div>

            <div id="help-refunds" x-show="open === 'refunds'" x-cloak
                class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900/80">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => 'Refunds', 'si' => 'ආපසු ගෙවීම්']) }}</h3>
                <ul class="mt-3 space-y-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <li>• {{ t(['en' => 'Eligible attendees can submit refund requests from Support in their account.', 'si' => 'සුදුසුකම් ඇති සහභාගීවන්නන්ට ගිණුමේ සහාය හරහා ආපසු ගෙවීම් ඉල්ලීම් ඉදිරිපත් කළ හැක.']) }}</li>
                    <li>• {{ t(['en' => 'Our Customer Relations team reviews each request and notifies you of the outcome by email.', 'si' => 'අපගේ පාරිභෝගික සම්බන්ධතා කණ්ඩායම එක් එක් ඉල්ලීම සමාලෝචනය කර ප්‍රතිඵලය ඊමේල් මගින් දන්වයි.']) }}</li>
                    <li>• {{ t(['en' => 'If an event is cancelled by the organizer, affected ticket holders are guided through the refund process.', 'si' => 'සංවිධායකයා ප්‍රසංගය අවලංගු කළහොත්, බලපෑමට ලක් වූ ටිකට් හිමියන්ට ආපසු ගෙවීම් ක්‍රියාවලිය මග පෙන්වනු ලැබේ.']) }}</li>
                </ul>
            </div>

            <div id="help-my-account" x-show="open === 'my-account'" x-cloak
                class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900/80">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => 'My Account', 'si' => 'මගේ ගිණුම']) }}</h3>
                <ul class="mt-3 space-y-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <li>• {{ t(['en' => 'Update your profile details from your account settings after signing in.', 'si' => 'පිවිසීමෙන් පසු ගිණුම් සැකසුම් වලින් ඔබේ පැතිකඩ විස්තර යාවත්කාලීන කරන්න.']) }}</li>
                    <li>• {{ t(['en' => 'Use a strong password and enable two-factor authentication when available for extra security.', 'si' => 'අමතර ආරක්ෂාව සඳහා ශක්තිමත් මුරපදයක් භාවිතා කර හැකි විට ද්විත්ව සත්‍යාපනය සක්‍රිය කරන්න.']) }}</li>
                    <li>• {{ t(['en' => 'Google sign-in is supported if you registered or linked your Google account.', 'si' => 'ඔබ Google ගිණුමක් ලියාපදිංචි කළේ හෝ සම්බන්ධ කළේ නම් Google පිවිසුම සහාය දක්වයි.']) }}</li>
                </ul>
            </div>

            <div id="help-event-updates" x-show="open === 'event-updates'" x-cloak
                class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900/80">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => 'Event Updates', 'si' => 'ප්‍රසංග යාවත්කාලීන']) }}</h3>
                <ul class="mt-3 space-y-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <li>• {{ t(['en' => 'Important event changes (time, venue, cancellations) are shared by email and in-app notifications when possible.', 'si' => 'වැදගත් ප්‍රසංග වෙනස්කම් (වේලාව, ස්ථානය, අවලංගු කිරීම්) හැකි විට ඊමේල් සහ යෙදුම් දැනුම්දීම් මගින් බෙදා ගනී.']) }}</li>
                    <li>• {{ t(['en' => 'Check your Calendar and Tickets views for the latest schedule details.', 'si' => 'නවතම කාලසටහන් විස්තර සඳහා ඔබේ දින දර්ශනය සහ ටිකට් දසුන් පරීක්ෂා කරන්න.']) }}</li>
                    <li>• {{ t(['en' => 'Follow event and host pages for announcements before event day.', 'si' => 'ප්‍රසංග දිනයට පෙර නිවේදන සඳහා ප්‍රසංග සහ සත්කාරක පිටු අනුගමනය කරන්න.']) }}</li>
                </ul>
            </div>

            <div id="help-support" x-show="open === 'support'" x-cloak
                class="rounded-2xl border border-slate-200/90 bg-white/90 p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900/80">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => 'Support Center', 'si' => 'සහාය මධ්‍යස්ථානය']) }}</h3>
                <ul class="mt-3 space-y-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <li>• {{ t(['en' => 'Signed-in attendees can submit inquiries about events and track replies under Support.', 'si' => 'පිවිසුණු සහභාගීවන්නන්ට ප්‍රසංග පිළිබඳ විමසුම් ඉදිරිපත් කර සහාය යටතේ පිළිතුරු ලුහුබැඳිය හැක.']) }}</li>
                    <li>• {{ t(['en' => 'Use Complaints to report issues and attach screenshots when helpful.', 'si' => 'ගැටලු වාර්තා කිරීමට පැමිණිලි භාවිතා කර අවශ්‍ය නම් තිර රූප අමුණන්න.']) }}</li>
                    <li>• {{ t(['en' => 'Prefer email help without signing in? Open Contact Us below.', 'si' => 'පිවිසීමකින් තොරව ඊමේල් උදව් අවශ්‍යද? පහත Contact Us විවෘත කරන්න.']) }}</li>
                </ul>
            </div>
        </section>

        {{-- Contact Us form --}}
        <section id="help-contact" x-show="open === 'contact'" x-cloak
            class="mt-8 scroll-mt-28 rounded-2xl border border-slate-200/90 bg-slate-100/80 p-5 sm:p-8 dark:border-slate-700 dark:bg-slate-900/60">

            <div class="max-w-3xl">
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    {{ t(['en' => 'CONTACT US', 'si' => 'අප අමතන්න']) }}
                </h2>
                <div class="mt-2 h-1 w-12 rounded-full bg-primary"></div>

                @if (session('status') === 'help-contact-sent')
                    <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                        {{ t(['en' => 'Thank you! Your message has been sent. We will get back to you soon.', 'si' => 'ස්තූතියි! ඔබේ පණිවිඩය යවා ඇත. අපි ඉක්මනින් ඔබව සම්බන්ධ කර ගන්නෙමු.']) }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('help.contact') }}" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="_open" value="contact">

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="sr-only">{{ t(['en' => 'Name', 'si' => 'නම']) }}</label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}"
                                placeholder="{{ t(['en' => 'Name', 'si' => 'නම']) }}" required
                                class="block w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:border-primary focus:ring-primary dark:border-slate-600 dark:bg-slate-950 dark:text-white">
                        </div>
                        <div>
                            <label for="email" class="sr-only">{{ t(['en' => 'Email', 'si' => 'විද්‍යුත් තැපෑල']) }}</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                placeholder="{{ t(['en' => 'Email', 'si' => 'විද්‍යුත් තැපෑල']) }}" required
                                class="block w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:border-primary focus:ring-primary dark:border-slate-600 dark:bg-slate-950 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label for="comment" class="sr-only">{{ t(['en' => 'Comment', 'si' => 'අදහස']) }}</label>
                        <textarea id="comment" name="comment" rows="6" required
                            placeholder="{{ t(['en' => 'Comment', 'si' => 'අදහස']) }}"
                            class="block w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:border-primary focus:ring-primary dark:border-slate-600 dark:bg-slate-950 dark:text-white">{{ old('comment') }}</textarea>
                    </div>

                    <div>
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-primary px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary-light focus:ring-offset-2">
                            {{ t(['en' => 'Submit', 'si' => 'ඉදිරිපත් කරන්න']) }}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-public-layout>
