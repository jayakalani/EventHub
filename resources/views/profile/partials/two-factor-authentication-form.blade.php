<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Two-Factor Authentication') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('Add an extra layer of security to your account using an authenticator app like Google Authenticator or Authy.') }}
        </p>
    </header>

    @if (session('status') === 'two-factor-enabled')
        <div class="mt-4 mb-6 p-4 rounded-xl bg-green-50 border border-green-200">
            <div class="flex gap-3">
                <i class="bi bi-check-circle text-green-600 text-lg flex-shrink-0"></i>
                <div>
                    <h3 class="font-semibold text-green-900 mb-2">Two-Factor Authentication Enabled</h3>
                    <p class="text-sm text-green-800">
                        Store your recovery codes in a safe place. Each code can only be used once.
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if (session('status') === 'two-factor-disabled')
        <div class="mt-4 mb-6 p-4 rounded-xl bg-yellow-50 border border-yellow-200">
            <p class="text-sm text-yellow-800">Two-factor authentication has been disabled.</p>
        </div>
    @endif

    @if (session('status') === 'recovery-codes-regenerated')
        <div class="mt-4 mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200">
            <p class="text-sm text-blue-800">New recovery codes have been generated. Save them securely.</p>
        </div>
    @endif

    @if (session('recovery_codes'))
        <div class="mt-4 mb-6 p-4 rounded-xl bg-gray-50 border border-gray-200">
            <h3 class="font-semibold text-gray-900 mb-3">Recovery Codes</h3>
            <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                @foreach (session('recovery_codes') as $code)
                    <span class="bg-white px-3 py-1 rounded border">{{ $code }}</span>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-gray-500">Each recovery code can only be used once.</p>
        </div>
    @endif

    @if ($user->hasTwoFactorEnabled())
        <div class="mt-6 p-4 rounded-lg bg-green-50 border border-green-200">
            <div class="flex items-center gap-2 text-green-800">
                <i class="bi bi-shield-check text-lg"></i>
                <span class="font-medium">Two-factor authentication is enabled.</span>
            </div>
            <p class="mt-2 text-sm text-green-700">
                Enabled on {{ $user->two_factor_confirmed_at->format('M d, Y') }}.
            </p>
        </div>

        <div class="mt-6 space-y-4">
            <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="space-y-4">
                @csrf
                <div>
                    <x-input-label for="regen_password" :value="__('Password')" />
                    <x-text-input id="regen_password" name="password" type="password" class="mt-1 block w-full"
                        required />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <x-secondary-button type="submit">
                    {{ __('Regenerate Recovery Codes') }}
                </x-secondary-button>
            </form>

            <form method="POST" action="{{ route('two-factor.disable') }}" class="space-y-4 border-t pt-4">
                @csrf
                @method('DELETE')
                <div>
                    <x-input-label for="disable_password" :value="__('Password to Disable 2FA')" />
                    <x-text-input id="disable_password" name="password" type="password" class="mt-1 block w-full"
                        required />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <x-danger-button type="submit"
                    onclick="return confirm('Are you sure you want to disable two-factor authentication?')">
                    {{ __('Disable Two-Factor Authentication') }}
                </x-danger-button>
            </form>
        </div>
    @elseif (session('two_factor_setup') || session('two_factor_setup_secret'))
        @php
            $setupSecret = session('two_factor_setup')['secret'] ?? session('two_factor_setup_secret');
            $setupQrCode =
                session('two_factor_setup')['qr_code'] ??
                app(\App\Services\TwoFactorService::class)->getQrCodeSvg($user, $setupSecret);
        @endphp
        <div class="mt-6 space-y-6">
            <p class="text-sm text-gray-600">
                Scan this QR code with your authenticator app, then enter the 6-digit code to confirm.
            </p>

            <div class="flex justify-center p-4 bg-white border rounded-lg">
                {!! $setupQrCode !!}
            </div>

            <div class="p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">Manual entry key:</p>
                <code class="text-sm font-mono break-all">{{ $setupSecret }}</code>
            </div>

            <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-4">
                @csrf
                <div>
                    <x-input-label for="two_factor_code" :value="__('Authentication Code')" />
                    <x-text-input id="two_factor_code" name="code" type="text" inputmode="numeric"
                        pattern="[0-9]*" maxlength="6" class="mt-1 block w-full font-mono tracking-widest" required
                        autofocus />
                    <x-input-error :messages="$errors->get('two_factor_code')" class="mt-2" />
                </div>
                <x-primary-button>{{ __('Confirm & Enable') }}</x-primary-button>
            </form>
        </div>
    @else
        <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-6">
            @csrf
            <x-primary-button>{{ __('Enable Two-Factor Authentication') }}</x-primary-button>
        </form>
    @endif
</section>
