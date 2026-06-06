<x-guest-layout>
    <div class="w-full px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
        <div
            class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-8 md:p-10 border border-gray-100 dark:border-gray-700">
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 dark:bg-primary-light/10 mb-4">
                    <i class="bi bi-shield-lock text-3xl text-primary dark:text-primary-light"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Two-Factor Authentication</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    Enter the 6-digit code from your authenticator app, or use a recovery code.
                </p>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                    <ul class="text-sm text-red-800 dark:text-red-200 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-6" id="totp-form">
                @csrf
                <div>
                    <label for="code" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Authentication Code
                    </label>
                    <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*"
                        maxlength="6" autofocus autocomplete="one-time-code" placeholder="000000"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-center text-2xl tracking-widest font-mono focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-primary to-primary-light hover:from-primary-dark hover:to-primary text-white font-semibold py-3 px-4 rounded-lg transition duration-200 shadow-lg flex items-center justify-center gap-2">
                    <i class="bi bi-check-circle"></i>
                    Verify
                </button>
            </form>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-3 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400">or</span>
                </div>
            </div>

            <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-4" id="recovery-form">
                @csrf
                <div>
                    <label for="recovery_code"
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Recovery Code
                    </label>
                    <input type="text" id="recovery_code" name="recovery_code" autocomplete="off"
                        placeholder="xxxx-xxxx-xxxx"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                </div>

                <button type="submit"
                    class="w-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold py-3 px-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Use Recovery Code
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-primary dark:text-primary-light hover:underline">
                    Back to login
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
