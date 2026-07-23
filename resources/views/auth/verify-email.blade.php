<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ t(['en' => "Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.", 'si' => 'ලියාපදිංචි වීම ගැන ස්තූතියි! ආරම්භ කිරීමට පෙර, අපි ඔබට ඊමේල් කළ සබැඳිය ක්ලික් කර ඔබේ විද්‍යුත් තැපැල් ලිපිනය තහවුරු කරන්න. ඊමේල් ලැබුණේ නැත්නම්, අපි තවත් එකක් යවමු.']) }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ t(['en' => 'A new verification link has been sent to the email address you provided during registration.', 'si' => 'ලියාපදිංචියේදී ඔබ ලබා දුන් විද්‍යුත් තැපැල් ලිපිනයට නව තහවුරු කිරීමේ සබැඳියක් යවා ඇත.']) }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ t(['en' => 'Resend Verification Email', 'si' => 'තහවුරු කිරීමේ ඊමේල් යළි යවන්න']) }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit"
                class="underline text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ t(['en' => 'Log Out', 'si' => 'ඉවත් වන්න']) }}
            </button>
        </form>
    </div>
</x-guest-layout>
