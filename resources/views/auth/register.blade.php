<x-guest-layout>
    <div class="w-full px-4 sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="mb-6 animate-[fadeIn_0.4s_ease-out] rounded-2xl border border-red-200 bg-red-50/90 p-4 shadow-sm backdrop-blur-sm dark:border-red-800 dark:bg-red-900/30">
                <div class="flex gap-3">
                    <i class="bi bi-exclamation-circle text-red-600 dark:text-red-400 text-lg flex-shrink-0 mt-0.5"></i>
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 xl:gap-16 items-start lg:items-center">
            {{-- Mobile hero --}}
            <div class="lg:hidden text-center animate-[fadeIn_0.5s_ease-out]">
                <div class="relative mx-auto mb-5 max-w-sm overflow-hidden rounded-2xl shadow-xl ring-1 ring-gray-200/60 dark:ring-gray-700/60">
                    <img src="{{ asset('cover-images/event-hub-illustration.png') }}" alt="EventHub Illustration"
                        class="h-36 w-full object-cover transition-transform duration-500 hover:scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-t from-primary/30 to-transparent"></div>
                </div>
                <h2 class="text-2xl font-bold text-primary dark:text-primary-light">Join EventHub</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Create your account and start managing events like a pro.
                </p>
            </div>

            {{-- Illustration column (desktop) --}}
            <div class="hidden lg:block lg:sticky lg:top-8 animate-[fadeIn_0.6s_ease-out]">
                <div class="relative">
                    <div class="absolute -inset-4 rounded-[2rem] bg-gradient-to-br from-primary/20 via-transparent to-indigo-400/20 blur-2xl"></div>
                    <div class="relative overflow-hidden rounded-3xl shadow-2xl ring-1 ring-gray-200/50 dark:ring-gray-700/50 transition-transform duration-500 hover:scale-[1.02]">
                        <img src="{{ asset('cover-images/event-hub-illustration.png') }}" alt="EventHub Illustration"
                            class="max-h-[24rem] xl:max-h-[28rem] w-full object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900/50 via-transparent to-transparent"></div>
                    </div>

                    <div class="absolute -bottom-4 -left-4 flex items-center gap-2 rounded-2xl bg-white/90 px-4 py-2.5 text-sm font-medium text-gray-700 shadow-lg ring-1 ring-gray-200/80 backdrop-blur-md dark:bg-gray-800/90 dark:text-gray-200 dark:ring-gray-700/80">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary dark:text-primary-light">
                            <i class="bi bi-ticket-perforated-fill text-sm"></i>
                        </span>
                        Free to get started
                    </div>
                    <div class="absolute -top-3 -right-3 flex items-center gap-2 rounded-2xl bg-white/90 px-4 py-2.5 text-sm font-medium text-gray-700 shadow-lg ring-1 ring-gray-200/80 backdrop-blur-md dark:bg-gray-800/90 dark:text-gray-200 dark:ring-gray-700/80">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600">
                            <i class="bi bi-stars text-sm"></i>
                        </span>
                        Join the community
                    </div>
                </div>

                <div class="mt-10 text-center lg:text-left">
                    <h2 class="text-3xl xl:text-4xl font-bold bg-gradient-to-r from-primary to-indigo-600 bg-clip-text text-transparent dark:from-primary-light dark:to-indigo-400">
                        Join EventHub
                    </h2>
                    <p class="mt-4 text-gray-600 dark:text-gray-400 text-lg leading-relaxed max-w-md">
                        Create your account and start managing events like a pro. Everything you need in one place.
                    </p>

                    <div class="mt-8 space-y-3">
                        <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:text-primary-light">
                                <i class="bi bi-check-lg"></i>
                            </span>
                            Quick registration in minutes
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:text-primary-light">
                                <i class="bi bi-check-lg"></i>
                            </span>
                            Secure account with Google sign-up
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:text-primary-light">
                                <i class="bi bi-check-lg"></i>
                            </span>
                            Full event management tools
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form column --}}
            <div class="animate-[fadeIn_0.5s_ease-out]">
                <div class="relative overflow-hidden rounded-3xl bg-white/80 shadow-2xl ring-1 ring-gray-200/60 backdrop-blur-xl transition-shadow duration-300 hover:shadow-[0_20px_60px_-15px_rgba(37,99,235,0.15)] dark:bg-gray-800/80 dark:ring-gray-700/60 dark:hover:shadow-[0_20px_60px_-15px_rgba(59,130,246,0.1)]">
                    <div class="pointer-events-none absolute -right-20 -top-20 h-40 w-40 rounded-full bg-primary/5 blur-2xl dark:bg-primary/10"></div>

                    <div class="relative max-h-[calc(100vh-6rem)] overflow-y-auto p-6 sm:p-8 md:p-10 scrollbar-thin">
                        <div class="mb-6 sm:mb-8">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-primary dark:bg-primary/20 dark:text-primary-light">
                                <i class="bi bi-person-plus"></i>
                                Register
                            </span>
                            <h3 class="mt-4 text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Create Account</h3>
                            <p class="mt-2 text-gray-600 dark:text-gray-400">Join EventHub and start managing your events</p>
                        </div>

                        <form method="POST" action="{{ route('register') }}" class="space-y-5">
                            @csrf

                            {{-- name fields --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="group">
                                    <label for="first_name"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">First
                                        Name</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="bi bi-person text-gray-400 text-lg transition-colors duration-200 group-focus-within:text-primary dark:group-focus-within:text-primary-light"></i>
                                        </div>
                                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}"
                                            required autofocus autocomplete="given-name" placeholder="Enter your first name"
                                            class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/90 dark:bg-gray-700/90 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 dark:focus:ring-primary-light/30 focus:border-primary dark:focus:border-primary-light hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200" />
                                    </div>
                                    @error('first_name')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="group">
                                    <label for="last_name"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Last
                                        Name</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="bi bi-person text-gray-400 text-lg transition-colors duration-200 group-focus-within:text-primary dark:group-focus-within:text-primary-light"></i>
                                        </div>
                                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}"
                                            required autocomplete="family-name" placeholder="Enter your last name"
                                            class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/90 dark:bg-gray-700/90 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 dark:focus:ring-primary-light/30 focus:border-primary dark:focus:border-primary-light hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200" />
                                    </div>
                                    @error('last_name')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            {{-- nic field --}}
                            <div class="group">
                                <label for="nic"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">NIC</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="bi bi-card-text text-gray-400 text-lg transition-colors duration-200 group-focus-within:text-primary dark:group-focus-within:text-primary-light"></i>
                                    </div>
                                    <input type="text" id="nic" name="nic" value="{{ old('nic') }}" required
                                        autofocus autocomplete="nic" placeholder="Enter your NIC"
                                        class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/90 dark:bg-gray-700/90 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 dark:focus:ring-primary-light/30 focus:border-primary dark:focus:border-primary-light hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200" />
                                </div>
                                @error('nic')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- email field --}}
                            <div class="group">
                                <label for="email"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Email
                                    Address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="bi bi-envelope text-gray-400 text-lg transition-colors duration-200 group-focus-within:text-primary dark:group-focus-within:text-primary-light"></i>
                                    </div>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                        autocomplete="username" placeholder="you@example.com"
                                        class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/90 dark:bg-gray-700/90 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 dark:focus:ring-primary-light/30 focus:border-primary dark:focus:border-primary-light hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200" />
                                </div>
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- contact & dob --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="group">
                                    <label for="contact_number"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Contact
                                        Number</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="bi bi-phone text-gray-400 text-lg transition-colors duration-200 group-focus-within:text-primary dark:group-focus-within:text-primary-light"></i>
                                        </div>
                                        <input type="text" id="contact_number" name="contact_number"
                                            value="{{ old('contact_number') }}" required autofocus
                                            autocomplete="contact_number" placeholder="Enter your contact_number"
                                            class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/90 dark:bg-gray-700/90 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 dark:focus:ring-primary-light/30 focus:border-primary dark:focus:border-primary-light hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200" />
                                    </div>
                                    @error('contact_number')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="group">
                                    <label for="date_of_birth"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Date Of
                                        Birth</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="bi bi-calendar-date text-gray-400 text-lg transition-colors duration-200 group-focus-within:text-primary dark:group-focus-within:text-primary-light"></i>
                                        </div>
                                        <input type="date" id="date_of_birth" name="date_of_birth"
                                            value="{{ old('date_of_birth') }}" required autofocus
                                            autocomplete="date_of_birth" placeholder="Enter your date_of_birth"
                                            class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/90 dark:bg-gray-700/90 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 dark:focus:ring-primary-light/30 focus:border-primary dark:focus:border-primary-light hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200" />
                                    </div>
                                    @error('date_of_birth')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            {{-- address field --}}
                            <div class="group">
                                <label for="address"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="bi bi-house text-gray-400 text-lg transition-colors duration-200 group-focus-within:text-primary dark:group-focus-within:text-primary-light"></i>
                                    </div>
                                    <input type="text" id="address" name="address" value="{{ old('address') }}"
                                        required autofocus autocomplete="address" placeholder="Enter your address"
                                        class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/90 dark:bg-gray-700/90 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 dark:focus:ring-primary-light/30 focus:border-primary dark:focus:border-primary-light hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200" />
                                </div>
                                @error('address')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- gender field --}}
                            <div class="group">
                                <label for="gender"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Gender</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                        <i class="bi bi-gender-ambiguous text-gray-400 text-lg transition-colors duration-200 group-focus-within:text-primary dark:group-focus-within:text-primary-light"></i>
                                    </div>
                                    <select id="gender" name="gender" required
                                        class="w-full appearance-none pl-12 pr-10 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/90 dark:bg-gray-700/90 text-gray-900 dark:text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 dark:focus:ring-primary-light/30 focus:border-primary dark:focus:border-primary-light hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200 cursor-pointer">
                                        <option value="" disabled selected>Select your gender</option>
                                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male
                                        </option>
                                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female
                                        </option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i class="bi bi-chevron-down text-gray-400"></i>
                                    </div>
                                </div>
                                @error('gender')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <i class="bi bi-exclamation-circle"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- password fields --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="group">
                                    <label for="password"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Password</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="bi bi-lock text-gray-400 text-lg transition-colors duration-200 group-focus-within:text-primary dark:group-focus-within:text-primary-light"></i>
                                        </div>
                                        <input type="password" id="password" name="password" required
                                            autocomplete="new-password" placeholder="••••••••"
                                            class="w-full pl-12 pr-12 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/90 dark:bg-gray-700/90 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 dark:focus:ring-primary-light/30 focus:border-primary dark:focus:border-primary-light hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200" />
                                        <button type="button" onclick="togglePassword()"
                                            class="absolute inset-y-0 right-0 pr-4 flex items-center rounded-r-xl text-gray-400 hover:text-primary dark:hover:text-primary-light transition-colors duration-200">
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

                                <div class="group">
                                    <label for="password_confirmation"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Confirm
                                        Password</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <i class="bi bi-lock-fill text-gray-400 text-lg transition-colors duration-200 group-focus-within:text-primary dark:group-focus-within:text-primary-light"></i>
                                        </div>
                                        <input type="password" id="password_confirmation" name="password_confirmation"
                                            required autocomplete="new-password" placeholder="••••••••"
                                            class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white/90 dark:bg-gray-700/90 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30 dark:focus:ring-primary-light/30 focus:border-primary dark:focus:border-primary-light hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200" />
                                    </div>
                                    @error('password_confirmation')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                            <i class="bi bi-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            {{-- terms checkbox --}}
                            <div class="flex items-start gap-3 rounded-xl border border-gray-200/80 bg-gray-50/50 p-4 dark:border-gray-700/80 dark:bg-gray-900/30">
                                <input id="terms" type="checkbox" name="terms"
                                    class="mt-0.5 w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-primary dark:text-primary-light focus:ring-2 focus:ring-primary dark:focus:ring-primary-light focus:ring-offset-0 dark:focus:ring-offset-gray-800 cursor-pointer transition" />
                                <label for="terms" class="text-sm text-gray-600 dark:text-gray-400 cursor-pointer leading-relaxed">
                                    I agree to the <a href="#"
                                        class="text-primary dark:text-primary-light hover:text-primary-dark dark:hover:text-primary font-semibold transition-colors hover:underline underline-offset-2">Terms
                                        of Service</a> and <a href="#"
                                        class="text-primary dark:text-primary-light hover:text-primary-dark dark:hover:text-primary font-semibold transition-colors hover:underline underline-offset-2">Privacy
                                        Policy</a>
                                </label>
                            </div>

                            {{-- submit button --}}
                            <button type="submit"
                                class="group w-full bg-gradient-to-r from-primary to-primary-light hover:from-primary-dark hover:to-primary dark:from-primary-light dark:to-primary dark:hover:from-primary dark:hover:to-primary-dark text-white font-semibold py-3.5 px-4 rounded-xl transition-all duration-300 transform hover:scale-[1.02] hover:shadow-xl hover:shadow-primary/25 active:scale-[0.98] shadow-lg flex items-center justify-center gap-2">
                                <i class="bi bi-person-plus transition-transform duration-300 group-hover:scale-110"></i>
                                Create Account
                            </button>
                        </form>

                        {{-- divider --}}
                        <div class="relative my-7">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200 dark:border-gray-600"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-white/80 dark:bg-gray-800/80 text-gray-500 dark:text-gray-400 backdrop-blur-sm">Or continue with</span>
                            </div>
                        </div>

                        {{-- google login --}}
                        <a href="{{ route('auth.google') }}"
                            class="group w-full flex items-center justify-center gap-3 py-3.5 px-4 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700/90 text-gray-900 dark:text-white font-semibold shadow-sm transition-all duration-300 hover:bg-gray-50 dark:hover:bg-gray-600 hover:border-gray-300 dark:hover:border-gray-500 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0">
                            <img src="https://img.icons8.com/color/48/000000/google-logo.png" alt="Google"
                                class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" />
                            <span>Sign up with Google</span>
                        </a>

                        {{-- login link & back to home --}}
                        <div class="mt-8 space-y-4 border-t border-gray-200 dark:border-gray-700 pt-6 text-center">
                            <p class="text-gray-600 dark:text-gray-400">
                                Already have an account?
                                <a href="{{ route('login') }}"
                                    class="font-semibold text-primary dark:text-primary-light hover:text-primary-dark dark:hover:text-primary transition-colors duration-200 hover:underline underline-offset-2">Sign
                                    in here</a>
                            </p>

                            <a href="{{ url('/') }}"
                                class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary-light transition-colors duration-200">
                                <i class="bi bi-arrow-left"></i>
                                Back to home
                            </a>
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

        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: rgba(37, 99, 235, 0.2);
            border-radius: 9999px;
        }
        .dark .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: rgba(59, 130, 246, 0.3);
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
