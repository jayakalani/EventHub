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
                    {{ t(['en' => 'By accessing or using :app, you agree to these Terms & Conditions. If you do not agree, please do not use the platform.', 'si' => ':app වෙත පිවිසීමෙන් හෝ භාවිතා කිරීමෙන්, ඔබ මෙම නියම සහ කොන්දේසිවලට එකඟ වේ. එකඟ නොවන්නේ නම්, කරුණාකර වේදිකාව භාවිතා නොකරන්න.'],) }}
                </p>
            </section>
        </div>
    </div>
</x-public-layout>
