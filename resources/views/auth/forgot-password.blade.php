<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ t(['en' => 'Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.', 'si' => 'මුරපදය අමතකද? ඔබේ විද්‍යුත් තැපැල් ලිපිනය අපට දන්වන්න — නව මුරපදයක් තෝරා ගැනීමට ඉඩ දෙන , මුරපද යළි සැකසුම් සබැඳියක් අපි ඔබට ඊමේල් කරමු.']) }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="t(['en' => 'Email', 'si' => 'විද්‍යුත් තැපෑල'])" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ t(['en' => 'Email Password Reset Link', 'si' => 'මුරපද යළි සැකසුම් සබැඳිය යවන්න']) }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
