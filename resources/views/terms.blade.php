<x-public-layout :title="t(['en' => 'Terms & Conditions', 'si' => 'නියම සහ කොන්දේසි'])">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary">{{ t(['en' => 'Legal', 'si' => 'නීතිමය']) }}</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
            {{ t(['en' => 'Terms & Conditions', 'si' => 'නියම සහ කොන්දේසි']) }}
        </h1>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
            {{ t(['en' => 'Last updated:', 'si' => 'අවසන් යාවත්කාලීනය:']) }} {{ date('F j, Y') }}
        </p>

        <div class="mt-8 space-y-8 rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-sm sm:p-8 dark:border-slate-700/80 dark:bg-slate-900/70">
            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '1. Acceptance of terms', 'si' => '1. නියම පිළිගැනීම']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t([
                        'en' => 'By accessing or using ' . config('app.name', 'EventHub') . ', you agree to these Terms & Conditions. If you do not agree, please do not use the platform.',
                        'si' => config('app.name', 'EventHub') . ' වෙත පිවිසීමෙන් හෝ භාවිතා කිරීමෙන්, ඔබ මෙම නියම සහ කොන්දේසිවලට එකඟ වේ. එකඟ නොවන්නේ නම්, කරුණාකර වේදිකාව භාවිතා නොකරන්න.',
                    ]) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '2. Accounts', 'si' => '2. ගිණුම්']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'You are responsible for keeping your account credentials secure and for all activity under your account. Provide accurate registration details and notify us if you suspect unauthorized access.', 'si' => 'ඔබේ ගිණුම් අක්තපත්‍ර ආරක්ෂිතව තබා ගැනීම සහ ඔබේ ගිණුම යටතේ සිදුවන සියලු ක්‍රියාකාරකම් සඳහා ඔබ වගකිව යුතුය. නිවැරදි ලියාපදිංචි විස්තර ලබා දෙන්න, අනවසර ප්‍රවේශයක් සැක කරන්නේ නම් අපට දන්වන්න.']) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '3. Event listings & bookings', 'si' => '3. ප්‍රසංග ලැයිස්තු සහ වෙන්කිරීම්']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'Event details are provided by organizers. Ticket availability, pricing, and schedules may change. Bookings are subject to organizer policies, including cancellation and refund rules where applicable.', 'si' => 'ප්‍රසංග විස්තර සපයන්නේ සංවිධායකයන්ය. ටිකට් ලබා ගත හැකි බව, මිල ගණන් සහ කාලසටහන් වෙනස් විය හැක. වෙන්කිරීම් අදාළ අවලංගු කිරීම් සහ ආපසු ගෙවීම් නීති ඇතුළු සංවිධායක ප්‍රතිපත්තිවලට යටත් වේ.']) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '4. Payments', 'si' => '4. ගෙවීම්']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'Payments processed through the platform must be completed with valid payment methods. Fees, taxes, and any service charges will be shown before you confirm a purchase.', 'si' => 'වේදිකාව හරහා සිදුවන ගෙවීම් වලංගු ගෙවීම් ක්‍රම සමඟ සම්පූර්ණ කළ යුතුය. ගාස්තු, බදු සහ සේවා ගාස්තු මිලදී ගැනීම තහවුරු කිරීමට පෙර පෙන්වනු ලැබේ.']) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '5. Acceptable use', 'si' => '5. පිළිගත හැකි භාවිතය']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'You agree not to misuse the platform, attempt unauthorized access, post harmful content, or interfere with other users, organizers, or system operations.', 'si' => 'වේදිකාව අනිසි ලෙස භාවිතා නොකිරීමට, අනවසර ප්‍රවේශයට උත්සාහ නොකිරීමට, හානිකර අන්තර්ගතයක් පළ නොකිරීමට, හෝ අනෙකුත් පරිශීලකයන්, සංවිධායකයන් හෝ පද්ධති මෙහෙයුම්වලට බාධා නොකිරීමට ඔබ එකඟ වේ.']) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '6. Limitation of liability', 'si' => '6. වගකීම් සීමා කිරීම']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t([
                        'en' => 'To the fullest extent permitted by law, ' . config('app.name', 'EventHub') . ' is not liable for indirect, incidental, or consequential damages arising from your use of the service or attendance at events.',
                        'si' => 'නීතියෙන් අවසර දී ඇති පරිදි උපරිම මට්ටමට, ' . config('app.name', 'EventHub') . ' සේවාව භාවිතා කිරීමෙන් හෝ ප්‍රසංගවලට සහභාගී වීමෙන් ඇතිවන වක්‍ර, අහඹු හෝ ප්‍රතිඵලදායී හානි සඳහා වගකිව යුතු නොවේ.',
                    ]) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '7. Changes', 'si' => '7. වෙනස්කම්']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'We may update these terms from time to time. Continued use of the platform after changes means you accept the updated Terms & Conditions.', 'si' => 'අපි වරින් වර මෙම නියම යාවත්කාලීන කළ හැක. වෙනස්කම්වලින් පසු වේදිකාව දිගටම භාවිතා කිරීමෙන් යාවත්කාලීන නියම සහ කොන්දේසි ඔබ පිළිගන්නා බව අදහස් වේ.']) }}
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t(['en' => '8. Contact', 'si' => '8. සම්බන්ධ වන්න']) }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ t(['en' => 'For questions about these terms, contact us through the support options available in your account or via the platform help channels.', 'si' => 'මෙම නියම පිළිබඳ ප්‍රශ්න සඳහා, ඔබේ ගිණුමේ ඇති සහාය විකල්ප හරහා හෝ වේදිකා උදව් නාලිකා හරහා අප අමතන්න.']) }}
                </p>
            </section>
        </div>
    </div>
</x-public-layout>
