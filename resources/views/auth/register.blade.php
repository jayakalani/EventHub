<x-guest-layout>
    <div class="w-full px-4 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <div class="flex gap-3">
                    <i class="bi bi-exclamation-circle text-red-600 dark:text-red-400 text-lg flex-shrink-0"></i>
                    <div>
                        <h3 class="font-semibold text-red-900 dark:text-red-300 mb-2">Registration Failed</h3>
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
                    <img src="{{ asset('cover-images/event-hub-illustration.png') }}" alt="EventHub Illustration"
                        class="rounded-3xl shadow-2xl max-h-96 w-full object-cover" />
                </div>
                <h2 class="text-3xl font-bold text-primary dark:text-primary-light mb-3">Join EventHub</h2>
                <p class="text-gray-600 dark:text-gray-400 text-lg leading-relaxed">
                    Create your account and start managing events like a pro.
                </p>
            </div>

            <!-- form column -->
            <div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-8 md:p-10 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">Create Account</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-8">Join EventHub and start managing your events</p>

                    <form method="POST" action="{{ route('register') }}" class="space-y-6">
                        @csrf


                        <!-- name field -->
                        <div>
                            <label for="first_name"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">First
                                Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-person text-gray-400 text-lg"></i>
                                </div>
                                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}"
                                    required autofocus autocomplete="given-name" placeholder="Enter your first name"
                                    class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                            </div>
                            @error('first_name')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="last_name"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Last
                                Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-person text-gray-400 text-lg"></i>
                                </div>
                                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}"
                                    required autocomplete="family-name" placeholder="Enter your last name"
                                    class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                            </div>
                            @error('last_name')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- nic field -->
                        <div>
                            <label for="nic"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">NIC</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-person text-gray-400 text-lg"></i>
                                </div>
                                <input type="text" id="nic" name="nic" value="{{ old('nic') }}" required
                                    autofocus autocomplete="nic" placeholder="Enter your NIC"
                                    class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                            </div>
                            @error('nic')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- email field -->
                        <div>
                            <label for="email"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Email
                                Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-envelope text-gray-400 text-lg"></i>
                                </div>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                    autocomplete="username" placeholder="you@example.com"
                                    class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                            </div>
                            @error('email')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- contact_number field -->
                        <div>
                            <label for="contact_number"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Contact
                                Number</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-phone text-gray-400 text-lg"></i>
                                </div>
                                <input type="text" id="contact_number" name="contact_number"
                                    value="{{ old('contact_number') }}" required autofocus
                                    autocomplete="contact_number" placeholder="Enter your contact_number"
                                    class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                            </div>
                            @error('contact_number')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- address field -->
                        <div>
                            <label for="address"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-house text-gray-400 text-lg"></i>
                                </div>
                                <input type="text" id="address" name="address" value="{{ old('address') }}"
                                    required autofocus autocomplete="address" placeholder="Enter your address"
                                    class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                            </div>
                            @error('address')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- date_of_birth field -->
                        <div>
                            <label for="date_of_birth"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Date Of
                                Birth</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-person text-gray-400 text-lg"></i>
                                </div>
                                <input type="date" id="date_of_birth" name="date_of_birth"
                                    value="{{ old('date_of_birth') }}" required autofocus
                                    autocomplete="date_of_birth" placeholder="Enter your date_of_birth"
                                    class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                            </div>
                            @error('date_of_birth')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- gender field -->
                        <div>
                            <label for="gender"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Gender</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-person text-gray-400 text-lg"></i>
                                </div>
                                <select id="gender" name="gender" required
                                    class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition">
                                    <option value="" disabled selected>Select your gender</option>
                                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male
                                    </option>
                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female
                                    </option>
                                </select>
                            </div>
                            @error('gender')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- password field -->
                        <div>
                            <label for="password"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-lock text-gray-400 text-lg"></i>
                                </div>
                                <input type="password" id="password" name="password" required
                                    autocomplete="new-password" placeholder="••••••••"
                                    class="w-full pl-12 pr-12 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                                <button type="button" onclick="togglePassword()"
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                    <i id="eye-icon"
                                        class="bi bi-eye text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-lg"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- confirm password field -->
                        <div>
                            <label for="password_confirmation"
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Confirm
                                Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-lock-fill text-gray-400 text-lg"></i>
                                </div>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    required autocomplete="new-password" placeholder="••••••••"
                                    class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:border-transparent transition" />
                            </div>
                            @error('password_confirmation')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="bi bi-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- terms checkbox -->
                        <div class="flex items-center gap-2">
                            <input id="terms" type="checkbox" name="terms"
                                class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-primary dark:text-primary-light focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:ring-offset-0 dark:focus:ring-offset-gray-800 cursor-pointer" />
                            <label for="terms" class="text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                                I agree to the <a href="#"
                                    class="text-primary dark:text-primary-light hover:text-primary-dark dark:hover:text-primary font-semibold">Terms
                                    of Service</a> and <a href="#"
                                    class="text-primary dark:text-primary-light hover:text-primary-dark dark:hover:text-primary font-semibold">Privacy
                                    Policy</a>
                            </label>
                        </div>
                        <!-- submit button -->
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-primary to-primary-light hover:from-primary-dark hover:to-primary dark:from-primary-light dark:to-primary dark:hover:from-primary dark:hover:to-primary-dark text-white font-semibold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105 active:scale-95 shadow-lg flex items-center justify-center gap-2">
                            <i class="bi bi-person-plus"></i>
                            Create Account
                        </button>
                    </form>

                    <!-- divider -->
                    <div class="relative my-8">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-3 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400">Or continue
                                with</span>
                        </div>
                    </div>
                    <!-- google login -->
                    <a href="{{ route('auth.google') }}"
                        class="w-full flex items-center justify-center gap-3 py-3 px-4 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600 font-semibold transition duration-200">
                        <img src="https://img.icons8.com/color/48/000000/google-logo.png" alt="Google"
                            class="w-5 h-5" />
                        <span>Sign up with Google</span>
                    </a>
                    <!-- login link -->
                    <div class="mt-8 text-center border-t border-gray-300 dark:border-gray-600 pt-6">
                        <p class="text-gray-600 dark:text-gray-400">
                            Already have an account?
                            <a href="{{ route('login') }}"
                                class="font-semibold text-primary dark:text-primary-light hover:text-primary-dark dark:hover:text-primary transition">Sign
                                in here</a>
                        </p>
                    </div>

                    <!-- Sign In Link -->
                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Already have an account?
                            <a href="{{ route('login') }}"
                                class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition-colors duration-200">
                                Sign in here
                            </a>
                        </p>
                    </div>

                    <!-- Back to Home -->
                    <div class="mt-4 text-center">
                        <a href="{{ url('/') }}"
                            class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to home
                        </a>
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
