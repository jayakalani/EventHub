<x-public-layout title="Privacy Policy">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary">Legal</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
            Privacy Policy
        </h1>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
            Last updated: {{ date('F j, Y') }}
        </p>

        <div class="mt-8 space-y-8 rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-sm sm:p-8 dark:border-slate-700/80 dark:bg-slate-900/70">
            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">1. Information we collect</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    We may collect account details (such as name and email), booking information, payment-related data
                    processed by payment providers, and usage data that helps us operate and improve {{ config('app.name', 'EventHub') }}.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">2. How we use information</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    We use your information to create and manage accounts, process bookings, send notifications,
                    provide customer support, prevent fraud, and improve platform performance and features.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">3. Sharing of information</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    We may share necessary booking details with event organizers and trusted service providers
                    (such as payment processors) who help us run the platform. We do not sell your personal information.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">4. Data security</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    We take reasonable technical and organizational measures to protect your data.
                    No method of transmission or storage is completely secure, so please use a strong password
                    and keep your login details private.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">5. Cookies & preferences</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    We may use cookies or local storage for essential features such as authentication sessions
                    and display preferences like dark mode.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">6. Your choices</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    You may update account information from your profile where available, and you can contact support
                    to request help with account-related privacy questions.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">7. Policy updates</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    We may update this Privacy Policy periodically. The “Last updated” date at the top of this page
                    will reflect the latest revision.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">8. Contact</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    If you have questions about this Privacy Policy or how your data is handled,
                    contact us through the platform support options.
                </p>
            </section>
        </div>
    </div>
</x-public-layout>
