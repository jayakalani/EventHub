<x-guest-layout>
    <div class="w-full px-4 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <div class="flex gap-3">
                    <i class="bi bi-exclamation-circle text-red-600 dark:text-red-400 text-lg flex-shrink-0"></i>
                    <div>
                        <h3 class="font-semibold text-red-900 dark:text-red-300 mb-2">Login Failed</h3>
                        <ul class="text-sm text-red-800 dark:text-red-200 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 lg:gap-12 items-center">
            <!-- Illustration column -->
            <div class="invisible md:visible text-center">
                <div class="mb-8">
                    <img  src="{{ asset('cover-images/event-hub-illustration.png') }}" alt="Insurance Illustration" class="rounded-3xl shadow-2xl max-h-96 w-full object-cover" />
                </div>
                <h2 class="text-3xl font-bold text-primary dark:text-primary-light mb-3">Welcome to InsurePortal</h2>
                <p class="text-gray-600 dark:text-gray-400 text-lg leading-relaxed">
                    We're here to fully digitalize and protect your client journey.
                </p>
            </div>

            <!-- form column -->
            <div>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-8 md:p-10 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">Welcome Back</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-8">Sign in to your account to continue</p>

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf
                        <!-- email field -->
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-envelope text-gray-400 text-lg"></i>
                                </div>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@example.com"
                                       class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                            </div>
                            @error('email')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- password field -->
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-lock text-gray-400 text-lg"></i>
                                </div>
                                <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                                       class="w-full pl-12 pr-12 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                                <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                    <i id="eye-icon" class="bi bi-eye text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-lg"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- remember & forgot -->
                        <div class="flex items-center justify-between">
                            <label for="remember_me" class="flex items-center gap-2 cursor-pointer">
                                <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-primary dark:text-primary-light focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:ring-offset-0 dark:focus:ring-offset-gray-800 cursor-pointer" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-sm text-primary dark:text-primary-light hover:text-primary-dark dark:hover:text-primary font-semibold transition">Forgot password?</a>
                            @endif
                        </div>
                        <!-- submit button -->
                        <button type="submit" class="w-full bg-gradient-to-r from-primary to-primary-light hover:from-primary-dark hover:to-primary dark:from-primary-light dark:to-primary dark:hover:from-primary dark:hover:to-primary-dark text-white font-semibold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105 active:scale-95 shadow-lg flex items-center justify-center gap-2">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Sign In
                        </button>
                    </form>

                    <!-- divider -->
                    <div class="relative my-8">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400">Or continue with</span>
                        </div>
                    </div>
                    <!-- google login -->
                    <a href="#" class="w-full flex items-center justify-center gap-3 py-3 px-4 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600 font-semibold transition duration-200">
                        <img src="https://img.icons8.com/color/48/000000/google-logo.png" alt="Google" class="w-5 h-5" />
                        <span>Sign in with Google</span>
                    </a>
                    <!-- register link -->
                    <div class="mt-8 text-center border-t border-gray-300 dark:border-gray-600 pt-6">
                        <p class="text-gray-600 dark:text-gray-400">
                            Don't have an account?
                            <a href="{{ route('register') }}" class="font-semibold text-primary dark:text-primary-light hover:text-primary-dark dark:hover:text-primary transition">Create one now</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.className = 'bi bi-eye-slash text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-lg';
            } else {
                passwordInput.type = 'password';
                eyeIcon.className = 'bi bi-eye text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-lg';
            }
        }
    </script>
</x-guest-layout>
