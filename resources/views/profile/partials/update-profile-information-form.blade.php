<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

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

    <!-- Success Message -->
    @if (session('status') === 'profile-updated')
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200">
            <div class="flex gap-3">
                <i class="bi bi-check-circle text-green-600 text-lg flex-shrink-0"></i>
                <div>
                    <h3 class="font-semibold text-green-900 mb-2">Profile Updated</h3>
                    <p class="text-sm text-green-800">
                        Your profile information has been successfully updated.
                    </p>
                </div>
            </div>
        </div>
    @endif


    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="first_name" :value="__('First Name')" />
            <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $user->first_name)"
                required autofocus autocomplete="first_name" />
            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
        </div>

        <div>
            <x-input-label for="last_name" :value="__('Last Name')" />
            <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $user->last_name)"
                required autofocus autocomplete="last_name" />
            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
        </div>

        <div>
            <x-input-label for="nic" :value="__('NIC')" />
            <x-text-input id="nic" name="nic" type="text" class="mt-1 block w-full" :value="old('nic', $user->nic)"
                required autofocus autocomplete="nic" />
            <x-input-error class="mt-2" :messages="$errors->get('nic')" />
        </div>

        <div>
            <x-input-label for="contact_number" :value="__('Contact Number')" />
            <x-text-input id="contact_number" name="contact_number" type="text" class="mt-1 block w-full"
                :value="old('contact_number', $user->contact_number)" required autofocus autocomplete="contact_number" />
            <x-input-error class="mt-2" :messages="$errors->get('contact_number')" />
        </div>

        <div>
            <x-input-label for="date_of_birth" :value="__('Date Of Birth')" />
            <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full"
                :value="old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d'))" required autofocus autocomplete="date_of_birth" />
            <x-input-error class="mt-2" :messages="$errors->get('date_of_birth')" />
        </div>

        <div>
            <x-input-label for="address" :value="__('Address')" />
            <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $user->address)"
                required autofocus autocomplete="address" />
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <div>
            <x-input-label for="gender" :value="__('Gender')" />

            <select id="gender" name="gender"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required>
                <option value="" disabled {{ old('gender', $user->gender?->value ?? '') ? '' : 'selected' }}>--
                    Select Gender --</option>
                <option value="male" {{ old('gender', $user->gender?->value ?? '') === 'male' ? 'selected' : '' }}>
                    Male</option>
                <option value="female" {{ old('gender', $user->gender?->value ?? '') === 'female' ? 'selected' : '' }}>
                    Female</option>
            </select>

            <x-input-error class="mt-2" :messages="$errors->get('gender')" />
        </div>


        <div>
            <x-input-label for="profile_photo" :value="__('Profile Photo')" />
            <input id="profile_photo" name="profile_photo" type="file" class="mt-1 block w-full" accept="image/*">
            <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
            @if ($user->profile_photo)
                <img src="{{ asset('uploads/users-profile-photos/' . $user->profile_photo) }}" alt="Profile Photo"
                    class="img-thumbnail mt-2" width="100">
            @endif
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)"
                required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification"
                            class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
