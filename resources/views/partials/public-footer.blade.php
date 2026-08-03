@php
    $eventsHref = request()->routeIs('welcome') ? '#events' : route('welcome') . '#events';
@endphp

<x-site-footer :home-href="route('welcome')" class="relative z-10">
    <x-slot:explore>
        <li>
            <x-footer-link :href="$eventsHref" :active="request()->routeIs('welcome')">
                {{ t(['en' => 'Events', 'si' => 'ප්‍රසංග']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('about')" :active="request()->routeIs('about')">
                {{ t(['en' => 'About', 'si' => 'අපි ගැන']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('help')" :active="request()->routeIs('help', 'help.contact')">
                {{ t(['en' => 'Help / FAQ', 'si' => 'උදව් / නිති ප්‍රශ්න']) }}
            </x-footer-link>
        </li>
    </x-slot:explore>

    <x-slot:legal>
        <li>
            <x-footer-link :href="route('terms')" :active="request()->routeIs('terms')">
                {{ t(['en' => 'Terms of Service', 'si' => 'සේවා කොන්දේසි']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('privacy')" :active="request()->routeIs('privacy')">
                {{ t(['en' => 'Privacy Policy', 'si' => 'රහස්‍යතා ප්‍රතිපත්තිය']) }}
            </x-footer-link>
        </li>
    </x-slot:legal>

    <x-slot:account>
        @guest
            <li>
                <x-footer-link :href="route('login')" :active="request()->routeIs('login')">
                    {{ t(['en' => 'Sign In', 'si' => 'පිවිසෙන්න']) }}
                </x-footer-link>
            </li>
            <li>
                <x-footer-link :href="route('register')" :active="request()->routeIs('register')">
                    {{ t(['en' => 'Register', 'si' => 'ලියාපදිංචි වන්න']) }}
                </x-footer-link>
            </li>
        @else
            <li>
                <x-footer-link :href="route('dashboard')">
                    {{ t(['en' => 'Dashboard', 'si' => 'විස්තර පුවරුව']) }}
                </x-footer-link>
            </li>
            <li>
                <x-footer-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                    {{ t(['en' => 'Profile', 'si' => 'පැතිකඩ']) }}
                </x-footer-link>
            </li>
        @endguest
    </x-slot:account>
</x-site-footer>
