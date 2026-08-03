<x-public-layout :title="t(['en' => 'Privacy Policy', 'si' => 'රහස්‍යතා ප්‍රතිපත්තිය'])">
    @php
        $toc = [
            ['id' => 'collect', 'label' => t(['en' => 'Information we collect', 'si' => 'අප රැස් කරන තොරතුරු'])],
            ['id' => 'use', 'label' => t(['en' => 'How we use information', 'si' => 'තොරතුරු භාවිතා කරන ආකාරය'])],
            ['id' => 'sharing', 'label' => t(['en' => 'Sharing of information', 'si' => 'තොරතුරු බෙදාගැනීම'])],
            ['id' => 'security', 'label' => t(['en' => 'Data security', 'si' => 'දත්ත ආරක්ෂාව'])],
            ['id' => 'cookies', 'label' => t(['en' => 'Cookies & preferences', 'si' => 'කුකීස් සහ මනාප'])],
            ['id' => 'choices', 'label' => t(['en' => 'Your choices', 'si' => 'ඔබේ තේරීම්'])],
            ['id' => 'updates', 'label' => t(['en' => 'Policy updates', 'si' => 'ප්‍රතිපත්ති යාවත්කාලීන'])],
            ['id' => 'contact', 'label' => t(['en' => 'Contact', 'si' => 'සම්බන්ධ වන්න'])],
        ];
    @endphp

    <x-legal-page
        active="privacy"
        icon="bi-shield-lock"
        :title="t(['en' => 'Privacy Policy', 'si' => 'රහස්‍යතා ප්‍රතිපත්තිය'])"
        :intro="t(['en' => 'This policy explains how ' . config('app.name', 'EventHub') . ' collects, uses, and protects your information when you use our platform.', 'si' => 'මෙම ප්‍රතිපත්තිය ' . config('app.name', 'EventHub') . ' ඔබේ තොරතුරු රැස් කරන, භාවිතා කරන සහ ආරක්ෂා කරන ආකාරය පැහැදිලි කරයි.'])"
        :toc="$toc"
    >
        <section id="collect">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '1. Information we collect', 'si' => '1. අප රැස් කරන තොරතුරු']) }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ t([
                    'en' => 'We may collect account details (such as name and email), booking information, payment-related data processed by payment providers, and usage data that helps us operate and improve ' . config('app.name', 'EventHub') . '.',
                    'si' => 'අපි ගිණුම් විස්තර (නම සහ විද්‍යුත් තැපෑල වැනි), වෙන්කිරීම් තොරතුරු, ගෙවීම් සපයන්නන් විසින් සැකසෙන ගෙවීම් දත්ත, සහ ' . config('app.name', 'EventHub') . ' ක්‍රියාත්මක කිරීමට හා වැඩිදියුණු කිරීමට උපකාරී භාවිත දත්ත රැස් කළ හැක.',
                ]) }}
            </p>
        </section>

        <section id="use">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '2. How we use information', 'si' => '2. තොරතුරු භාවිතා කරන ආකාරය']) }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ t(['en' => 'We use your information to create and manage accounts, process bookings, send notifications, provide customer support, prevent fraud, and improve platform performance and features.', 'si' => 'ගිණුම් සෑදීමට සහ කළමනාකරණයට, වෙන්කිරීම් සැකසීමට, දැනුම්දීම් යැවීමට, පාරිභෝගික සහාය ලබා දීමට, වංචා වැළැක්වීමට, සහ වේදිකා කාර්යසාධනය හා විශේෂාංග වැඩිදියුණු කිරීමට අපි ඔබේ තොරතුරු භාවිතා කරමු.']) }}
            </p>
        </section>

        <section id="sharing">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '3. Sharing of information', 'si' => '3. තොරතුරු බෙදාගැනීම']) }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ t(['en' => 'We may share necessary booking details with event organizers and trusted service providers (such as payment processors) who help us run the platform. We do not sell your personal information.', 'si' => 'වේදිකාව ක්‍රියාත්මක කිරීමට උපකාරී සංවිධායකයන් සහ විශ්වාසනීය සේවා සපයන්නන් (ගෙවීම් සැකසුම්කරුවන් වැනි) සමඟ අවශ්‍ය වෙන්කිරීම් විස්තර බෙදා ගත හැක. අපි ඔබේ පුද්ගලික තොරතුරු විකුණන්නේ නැත.']) }}
            </p>
        </section>

        <section id="security">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '4. Data security', 'si' => '4. දත්ත ආරක්ෂාව']) }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ t(['en' => 'We take reasonable technical and organizational measures to protect your data. No method of transmission or storage is completely secure, so please use a strong password and keep your login details private.', 'si' => 'ඔබේ දත්ත ආරක්ෂා කිරීමට අපි සාධාරණ තාක්ෂණික සහ සංවිධානාත්මක පියවර ගනිමු. සම්ප්‍රේෂණයේ හෝ ගබඩා කිරීමේ ක්‍රමයක් සම්පූර්ණයෙන් ආරක්ෂිත නොවන බැවින්, ශක්තිමත් මුරපදයක් භාවිතා කර ඔබේ පිවිසුම් විස්තර පුද්ගලිකව තබා ගන්න.']) }}
            </p>
        </section>

        <section id="cookies">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '5. Cookies & preferences', 'si' => '5. කුකීස් සහ මනාප']) }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ t(['en' => 'We may use cookies or local storage for essential features such as authentication sessions and display preferences like dark mode.', 'si' => 'සත්‍යාපන සැසි සහ අඳුරු ප්‍රකාරය වැනි සංදර්ශක මනාප වැනි අත්‍යවශ්‍ය විශේෂාංග සඳහා කුකීස් හෝ දේශීය ගබඩාව භාවිතා කළ හැක.']) }}
            </p>
        </section>

        <section id="choices">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '6. Your choices', 'si' => '6. ඔබේ තේරීම්']) }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ t(['en' => 'You may update account information from your profile where available, and you can contact support to request help with account-related privacy questions.', 'si' => 'ලබා ගත හැකි තැන්වල ඔබේ පැතිකඩෙන් ගිණුම් තොරතුරු යාවත්කාලීන කළ හැකි අතර, ගිණුම් සම්බන්ධ රහස්‍යතා ප්‍රශ්න සඳහා සහාය අමතන්න.']) }}
            </p>
        </section>

        <section id="updates">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '7. Policy updates', 'si' => '7. ප්‍රතිපත්ති යාවත්කාලීන']) }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ t(['en' => 'We may update this Privacy Policy periodically. The “Last updated” date at the top of this page will reflect the latest revision.', 'si' => 'අපි මෙම රහස්‍යතා ප්‍රතිපත්තිය වරින් වර යාවත්කාලීන කළ හැක. මෙම පිටුවේ ඉහළින් ඇති “අවසන් යාවත්කාලීනය” දිනය නවතම සංශෝධනය පෙන්වයි.']) }}
            </p>
        </section>

        <section id="contact">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '8. Contact', 'si' => '8. සම්බන්ධ වන්න']) }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ t(['en' => 'If you have questions about this Privacy Policy or how your data is handled, contact us through the platform support options.', 'si' => 'මෙම රහස්‍යතා ප්‍රතිපත්තිය හෝ ඔබේ දත්ත හසුරුවන ආකාරය පිළිබඳ ප්‍රශ්න තිබේ නම්, වේදිකා සහාය විකල්ප හරහා අප අමතන්න.']) }}
            </p>
        </section>
    </x-legal-page>
</x-public-layout>
