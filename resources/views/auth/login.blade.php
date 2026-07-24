<x-guest-layout>
    <div class="w-full px-4 sm:px-6 lg:px-8">
        @if (session('error'))
            <div class="mb-6 animate-[fadeIn_0.4s_ease-out] rounded-2xl border border-red-200 bg-red-50/90 p-4 shadow-sm backdrop-blur-sm dark:border-red-800 dark:bg-red-900/30">
                <p class="flex items-center gap-2 text-sm text-red-800 dark:text-red-200">
                    <i class="bi bi-exclamation-triangle-fill text-red-500"></i>
                    {{ session('error') }}
                </p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 animate-[fadeIn_0.4s_ease-out] rounded-2xl border border-red-200 bg-red-50/90 p-4 shadow-sm backdrop-blur-sm dark:border-red-800 dark:bg-red-900/30">
                <div class="flex gap-3">
                    <i class="bi bi-exclamation-circle text-red-600 dark:text-red-400 text-lg flex-shrink-0 mt-0.5"></i>
                    <div>
                        <h3 class="font-semibold text-red-900 dark:text-red-300 mb-2">{{ t(['en' => 'Login Failed', 'si' => 'පිවිසීම අසාර්ථකයි']) }}</h3>
                        <ul class="text-sm text-red-800 dark:text-red-200 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 xl:gap-16 items-center">
            {{-- Mobile hero --}}
            <div class="lg:hidden text-center animate-[fadeIn_0.5s_ease-out]">
                <div class="relative mx-auto mb-5 max-w-sm overflow-hidden rounded-2xl shadow-xl ring-1 ring-gray-200/60 dark:ring-gray-700/60">
                    <img src="{{ asset('cover-images/event-hub-illustration.png') }}" alt="{{ t(['en' => 'EventHub Illustration', 'si' => 'EventHub නිදර්ශනය']) }}"
                        class="h-40 w-full object-cover transition-transform duration-500 hover:scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-t from-primary/30 to-transparent"></div>
                </div>
                <h2 class="text-2xl font-bold text-[#0F0363] dark:text-[#C4B5FD]">{{ t(['en' => 'Welcome to EventHub', 'si' => 'EventHub වෙත සාදරයෙන් පිළිගනිමු']) }}</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ t(['en' => 'Discover, join, and manage events effortlessly.', 'si' => 'ප්‍රසංගය් පහසුවෙන් සොයා ගන්න, එක්වන්න සහ කළමනාකරණය කරන්න.']) }}
                </p>
            </div>

            {{-- Illustration column (desktop) --}}
            <div class="hidden lg:block animate-[fadeIn_0.6s_ease-out]">
                <div class="relative">
                    <div class="absolute -inset-4 rounded-[2rem] bg-gradient-to-br from-primary/20 via-transparent to-indigo-400/20 blur-2xl"></div>
                    <div class="relative overflow-hidden rounded-3xl shadow-2xl ring-1 ring-gray-200/50 dark:ring-gray-700/50 transition-transform duration-500 hover:scale-[1.02]">
                        <img src="{{ asset('cover-images/event-hub-illustration.png') }}" alt="{{ t(['en' => 'EventHub Illustration', 'si' => 'EventHub නිදර්ශනය']) }}"
                            class="max-h-[28rem] w-full object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/50 via-transparent to-transparent"></div>
                    </div>

                    {{-- Floating feature badges --}}
                    <div class="absolute -bottom-4 -left-4 flex items-center gap-2 rounded-2xl bg-white/90 px-4 py-2.5 text-sm font-medium text-gray-700 shadow-lg ring-1 ring-gray-200/80 backdrop-blur-md dark:bg-gray-800/90 dark:text-gray-200 dark:ring-gray-700/80">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary dark:text-primary-light">
                            <i class="bi bi-lightning-charge-fill text-sm"></i>
                        </span>
                        {{ t(['en' => 'Fast & secure access', 'si' => 'වේගවත් සහ ආරක්ෂිත ප්‍රවේශය']) }}
                    </div>
                    <div class="absolute -top-3 -right-3 flex items-center gap-2 rounded-2xl bg-white/90 px-4 py-2.5 text-sm font-medium text-gray-700 shadow-lg ring-1 ring-gray-200/80 backdrop-blur-md dark:bg-gray-800/90 dark:text-gray-200 dark:ring-gray-700/80">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                            <i class="bi bi-shield-check text-sm"></i>
                        </span>
                        {{ t(['en' => 'Protected sign-in', 'si' => 'ආරක්ෂිත පිවිසීම']) }}
                    </div>
                </div>

                <div class="mt-10 text-center lg:text-left">
                    <h2 class="text-3xl xl:text-4xl font-bold bg-gradient-to-r from-primary to-indigo-600 bg-clip-text text-transparent dark:from-primary-light dark:to-indigo-400">
                        {{ t(['en' => 'Welcome to EventHub', 'si' => 'EventHub වෙත සාදරයෙන් පිළිගනිමු']) }}
                    </h2>
                    <p class="mt-4 text-gray-600 dark:text-gray-400 text-lg leading-relaxed max-w-md">
                        {{ t(['en' => 'Discover, join, and manage events effortlessly. Your next great experience starts here.', 'si' => 'ප්‍රසංගය් පහසුවෙන් සොයා ගන්න, එක්වන්න සහ කළමනාකරණය කරන්න. ඔබේ ඊළඟ විශිෂ්ට ප්‍රසංගය මෙතැනින් ආරම්භ වේ.']) }}
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3 justify-center lg:justify-start">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 text-sm text-gray-600 shadow-sm ring-1 ring-gray-200/80 dark:bg-gray-800/80 dark:text-gray-300 dark:ring-gray-700/80">
                            <i class="bi bi-calendar-check text-primary dark:text-primary-light"></i>
                            {{ t(['en' => 'Event management', 'si' => 'ප්‍රසංගය් කළමනාකරණය']) }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 text-sm text-gray-600 shadow-sm ring-1 ring-gray-200/80 dark:bg-gray-800/80 dark:text-gray-300 dark:ring-gray-700/80">
                            <i class="bi bi-people text-primary dark:text-primary-light"></i>
                            {{ t(['en' => 'Community driven', 'si' => 'ප්‍රජා මූලික']) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Form column --}}
            <div class="animate-[fadeIn_0.5s_ease-out]">
                <div class="relative overflow-hidden rounded-3xl bg-white/80 p-6 sm:p-8 md:p-10 shadow-2xl ring-1 ring-gray-200/60 backdrop-blur-xl transition-shadow duration-300 hover:shadow-[0_20px_60px_-15px_rgba(37,99,235,0.15)] dark:bg-gray-800/80 dark:ring-gray-700/60 dark:hover:shadow-[0_20px_60px_-15px_rgba(59,130,246,0.1)]">
                    <div class="pointer-events-none absolute -right-20 -top-20 h-40 w-40 rounded-full bg-primary/5 blur-2xl dark:bg-primary/10"></div>

                    <div class="relative">
                        <div class="mb-8">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-primary dark:bg-primary/20 dark:text-primary-light">
                                <i class="bi bi-box-arrow-in-right"></i>
                                {{ t(['en' => 'Sign in', 'si' => 'පිවිසෙන්න']) }}
                            </span>
                            <h3 class="mt-4 text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ t(['en' => 'Welcome Back', 'si' => 'නැවත සාදරයෙන් පිළිගනිමු']) }}</h3>
                            <p class="mt-2 text-gray-600 dark:text-gray-400">{{ t(['en' => 'Sign in to your account to continue', 'si' => 'ඉදිරියට යාමට ඔබේ ගිණුමට පිවිසෙන්න']) }}</p>
                        </div>

                        <form method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf
                            {{-- email field --}}
                            <div class="group">
                                <label for="email"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ t(['en' => 'Email Address', 'si' => 'විද්‍යුත් තැපැල් ලිපිනය']) }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="bi bi-envelope text-gray-400 text-lg transition-colors duration-200 group-focus-within:text-primary dark:group-focus-within:text-primary-light"></i>
                                    </div>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                        autofocus autocomplete="username" placeholder="you@example.com"
                                        class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/90 dark:bg-gray-700/90 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 dark:focus:ring-primary-light/30 focus:border-primary dark:focus:border-primary-light hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200" />
                                </div>
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            {{-- password field --}}
                            <div class="group">
                                <label for="password"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ t(['en' => 'Password', 'si' => 'මුරපදය']) }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="bi bi-lock text-gray-400 text-lg transition-colors duration-200 group-focus-within:text-primary dark:group-focus-within:text-primary-light"></i>
                                    </div>
                                    <input type="password" id="password" name="password" required
                                        autocomplete="current-password" placeholder="••••••••"
                                        class="w-full pl-12 pr-12 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/90 dark:bg-gray-700/90 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 dark:focus:ring-primary-light/30 focus:border-primary dark:focus:border-primary-light hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200" />
                                    <button type="button" onclick="togglePassword()"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center rounded-r-xl text-gray-400 hover:text-primary dark:hover:text-primary-light transition-colors duration-200"
                                        aria-label="{{ t(['en' => 'Toggle password visibility', 'si' => 'මුරපදය පෙන්වන්න/සඟවන්න']) }}">
                                        <i id="eye-icon" class="bi bi-eye text-lg"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            {{-- remember & forgot --}}
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pt-1">
                                <label for="remember_me" class="flex items-center gap-2.5 cursor-pointer group/check">
                                    <input id="remember_me" type="checkbox" name="remember"
                                        class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-primary dark:text-primary-light focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:ring-offset-0 dark:focus:ring-offset-gray-800 cursor-pointer transition" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400 group-hover/check:text-gray-800 dark:group-hover/check:text-gray-300 transition-colors">{{ t(['en' => 'Remember me', 'si' => 'මතක තබා ගන්න']) }}</span>
                                </label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}"
                                        class="text-sm text-primary dark:text-primary-light hover:text-primary-dark dark:hover:text-primary font-semibold transition-colors duration-200 hover:underline underline-offset-2">{{ t(['en' => 'Forgot password?', 'si' => 'මුරපදය අමතකද?']) }}</a>
                                @endif
                            </div>
                            {{-- submit button --}}
                            <button type="submit"
                                class="group w-full bg-gradient-to-r from-primary to-primary-light hover:from-primary-dark hover:to-primary dark:from-primary-light dark:to-primary dark:hover:from-primary dark:hover:to-primary-dark text-white font-semibold py-3.5 px-4 rounded-xl transition-all duration-300 transform hover:scale-[1.02] hover:shadow-xl hover:shadow-primary/25 active:scale-[0.98] shadow-lg flex items-center justify-center gap-2">
                                <i class="bi bi-box-arrow-in-right transition-transform duration-300 group-hover:translate-x-0.5"></i>
                                {{ t(['en' => 'Sign In', 'si' => 'පිවිසෙන්න']) }}
                            </button>
                        </form>

                        {{-- divider --}}
                        <div class="relative my-7">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200 dark:border-gray-600"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-white/80 dark:bg-gray-800/80 text-gray-500 dark:text-gray-400 backdrop-blur-sm">{{ t(['en' => 'Or continue with', 'si' => 'නැතහොත් මෙයින් ඉදිරියට']) }}</span>
                            </div>
                        </div>
                        {{-- google login --}}
                        <a href="{{ route('auth.google') }}"
                            class="group w-full flex items-center justify-center gap-3 py-3.5 px-4 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/90 text-gray-900 dark:text-white font-semibold shadow-sm transition-all duration-300 hover:bg-gray-50 dark:hover:bg-gray-600 hover:border-gray-300 dark:hover:border-gray-500 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0">
                            <img src="https://img.icons8.com/color/48/000000/google-logo.png" alt="Google"
                                class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" />
                            <span>{{ t(['en' => 'Sign in with Google', 'si' => 'Google සමඟ පිවිසෙන්න']) }}</span>
                        </a>
                        {{-- register link --}}
                        <div class="mt-8 text-center border-t border-gray-200 dark:border-gray-700 pt-6">
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ t(['en' => "Don't have an account?", 'si' => 'ගිණුමක් නැද්ද?']) }}
                                <a href="{{ route('register') }}"
                                    class="font-semibold text-primary dark:text-primary-light hover:text-primary-dark dark:hover:text-primary transition-colors duration-200 hover:underline underline-offset-2">{{ t(['en' => 'Create one now', 'si' => 'දැන් එකක් සාදන්න']) }}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.className = 'bi bi-eye-slash text-lg';
            } else {
                passwordInput.type = 'password';
                eyeIcon.className = 'bi bi-eye text-lg';
            }
        }
    </script>
</x-guest-layout>
