<x-guest-layout>
    <div class="w-full px-4 sm:px-6 lg:px-8 max-w-2xl mx-auto">
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <ul class="text-sm text-red-800 dark:text-red-200 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div
            class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-8 md:p-10 border border-gray-100 dark:border-gray-700">
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 dark:bg-primary-light/10 mb-4">
                    <img src="https://img.icons8.com/color/48/000000/google-logo.png" alt="Google" class="w-8 h-8" />
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Complete Your Profile</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    You signed in with Google as <strong>{{ $user->email }}</strong>.
                    Please fill in the remaining details to finish setting up your account.
                </p>
            </div>

            <form method="POST" action="{{ route('auth.google.complete-profile.store') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="first_name"
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">First Name</label>
                        <input type="text" id="first_name" name="first_name"
                            value="{{ old('first_name', $user->first_name) }}" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                    <div>
                        <label for="last_name"
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Last Name</label>
                        <input type="text" id="last_name" name="last_name"
                            value="{{ old('last_name', $user->last_name) }}" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary" />
                    </div>
                </div>

                <div>
                    <label for="nic"
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">NIC</label>
                    <input type="text" id="nic" name="nic" value="{{ old('nic') }}" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>

                <div>
                    <label for="contact_number"
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number') }}"
                        required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>

                <div>
                    <label for="date_of_birth"
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}"
                        required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>

                <div>
                    <label for="address"
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Address</label>
                    <input type="text" id="address" name="address" value="{{ old('address') }}" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary" />
                </div>

                <div>
                    <label for="gender"
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Gender</label>
                    <select id="gender" name="gender" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Select gender</option>
                        <option value="male" @selected(old('gender') === 'male')>Male</option>
                        <option value="female" @selected(old('gender') === 'female')>Female</option>
                    </select>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-primary to-primary-light hover:from-primary-dark hover:to-primary text-white font-semibold py-3 px-4 rounded-lg transition duration-200 shadow-lg">
                    Complete Profile & Continue
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
