<x-public-layout :title="t(['en' => 'Privacy Policy', 'si' => 'රහස්‍යතා ප්‍රතිපත්තිය'])">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary">{{ t(['en' => 'Legal', 'si' => 'නීතිමය']) }}</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
            {{ t(['en' => 'Privacy Policy', 'si' => 'රහස්‍යතා ප්‍රතිපත්තිය']) }}
        </h1>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
            {{ t(['en' => 'Last updated:', 'si' => 'අවසන් යාවත්කාලීනය:']) }} {{ date('F j, Y') }}
        </p>

        <div class="mt-8 space-y-8 rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-sm sm:p-8 dark:border-slate-700/80 dark:bg-slate-900/70">
            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '1. Information we collect', 'si' => '1. අප රැස් කරන තොරතුරු']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t([
                        'en' => 'We may collect account details (such as name and email), booking information, payment-related data processed by payment providers, and usage data that helps us operate and improve ' . config('app.name', 'EventHub') . '.',
                        'si' => 'අපි ගිණුම් විස්තර (නම සහ විද්‍යුත් තැපෑල වැනි), වෙන්කිරීම් තොරතුරු, ගෙවීම් සපයන්නන් විසින් සැකසෙන ගෙවීම් දත්ත, සහ ' . config('app.name', 'EventHub') . ' ක්‍රියාත්මක කිරීමට හා වැඩිදියුණු කිරීමට උපකාරී භාවිත දත්ත රැස් කළ හැක.',
                    ]) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '2. How we use information', 'si' => '2. තොරතුරු භාවිතා කරන ආකාරය']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'We use your information to create and manage accounts, process bookings, send notifications, provide customer support, prevent fraud, and improve platform performance and features.', 'si' => 'ගිණුම් සෑදීමට සහ කළමනාකරණයට, වෙන්කිරීම් සැකසීමට, දැනුම්දීම් යැවීමට, පාරිභෝගික සහාය ලබා දීමට, වංචා වැළැක්වීමට, සහ වේදිකා කාර්යසාධනය හා විශේෂාංග වැඩිදියුණු කිරීමට අපි ඔබේ තොරතුරු භාවිතා කරමු.']) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '3. Sharing of information', 'si' => '3. තොරතුරු බෙදාගැනීම']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'We may share necessary booking details with event organizers and trusted service providers (such as payment processors) who help us run the platform. We do not sell your personal information.', 'si' => 'වේදිකාව ක්‍රියාත්මක කිරීමට උපකාරී සංවිධායකයන් සහ විශ්වාසනීය සේවා සපයන්නන් (ගෙවීම් සැකසුම්කරුවන් වැනි) සමඟ අවශ්‍ය වෙන්කිරීම් විස්තර බෙදා ගත හැක. අපි ඔබේ පුද්ගලික තොරතුරු විකුණන්නේ නැත.']) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '4. Data security', 'si' => '4. දත්ත ආරක්ෂාව']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'We take reasonable technical and organizational measures to protect your data. No method of transmission or storage is completely secure, so please use a strong password and keep your login details private.', 'si' => 'ඔබේ දත්ත ආරක්ෂා කිරීමට අපි සාධාරණ තාක්ෂණික සහ සංවිධානාත්මක පියවර ගනිමු. සම්ප්‍රේෂණයේ හෝ ගබඩා කිරීමේ ක්‍රමයක් සම්පූර්ණයෙන් ආරක්ෂිත නොවන බැවින්, ශක්තිමත් මුරපදයක් භාවිතා කර ඔබේ පිවිසුම් විස්තර පුද්ගලිකව තබා ගන්න.']) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '5. Cookies & preferences', 'si' => '5. කුකීස් සහ මනාප']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'We may use cookies or local storage for essential features such as authentication sessions and display preferences like dark mode.', 'si' => 'සත්‍යාපන සැසි සහ අඳුරු ප්‍රකාරය වැනි සංදර්ශක මනාප වැනි අත්‍යවශ්‍ය විශේෂාංග සඳහා කුකීස් හෝ දේශීය ගබඩාව භාවිතා කළ හැක.']) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '6. Your choices', 'si' => '6. ඔබේ තේරීම්']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'You may update account information from your profile where available, and you can contact support to request help with account-related privacy questions.', 'si' => 'ලබා ගත හැකි තැන්වල ඔබේ පැතිකඩෙන් ගිණුම් තොරතුරු යාවත්කාලීන කළ හැකි අතර, ගිණුම් සම්බන්ධ රහස්‍යතා ප්‍රශ්න සඳහා සහාය අමතන්න.']) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '7. Policy updates', 'si' => '7. ප්‍රතිපත්ති යාවත්කාලීන']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'We may update this Privacy Policy periodically. The “Last updated” date at the top of this page will reflect the latest revision.', 'si' => 'අපි මෙම රහස්‍යතා ප්‍රතිපත්තිය වරින් වර යාවත්කාලීන කළ හැක. මෙම පිටුවේ ඉහළින් ඇති “අවසන් යාවත්කාලීනය” දිනය නවතම සංශෝධනය පෙන්වයි.']) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '8. Contact', 'si' => '8. සම්බන්ධ වන්න']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'If you have questions about this Privacy Policy or how your data is handled, contact us through the platform support options.', 'si' => 'මෙම රහස්‍යතා ප්‍රතිපත්තිය හෝ ඔබේ දත්ත හසුරුවන ආකාරය පිළිබඳ ප්‍රශ්න තිබේ නම්, වේදිකා සහාය විකල්ප හරහා අප අමතන්න.']) }}
                </p>
            </section>
        </div>
    </div>
</x-public-layout>
