<x-site-footer :home-href="route('attendee.dashboard')">
    <x-slot:explore>
        <li>
            <x-footer-link :href="route('attendee.dashboard')" :active="request()->routeIs('attendee.dashboard', 'attendee.events.*')">
                {{ t(['en' => 'Events', 'si' => 'ප්‍රසංග']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('attendee.saved.index')" :active="request()->routeIs('attendee.saved.*')">
                {{ t(['en' => 'Saved', 'si' => 'සුරකින ලද']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('attendee.hosts.index')" :active="request()->routeIs('attendee.hosts.*')">
                {{ t(['en' => 'Hosts', 'si' => 'සත්කාරක']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('attendee.bookings.index')" :active="request()->routeIs('attendee.bookings.*')">
                {{ t(['en' => 'Tickets', 'si' => 'ටිකට්']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('attendee.cart.index')" :active="request()->routeIs('attendee.cart.*')">
                {{ t(['en' => 'Cart', 'si' => 'කාර්ට්']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('attendee.support.index')" :active="request()->routeIs('attendee.support.*')">
                {{ t(['en' => 'Support', 'si' => 'සහාය']) }}
            </x-footer-link>
        </li>
    </x-slot:explore>

    <x-slot:legal>
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
        <li>
            <x-footer-link :href="route('attendee.dashboard')" :active="request()->routeIs('attendee.dashboard')">
                {{ t(['en' => 'Dashboard', 'si' => 'විස්තර පුවරුව']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('attendee.wallet.index')" :active="request()->routeIs('attendee.wallet.*')">
                {{ t(['en' => 'Wallet', 'si' => 'පසුම්බිය']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                {{ t(['en' => 'Profile', 'si' => 'පැතිකඩ']) }}
            </x-footer-link>
        </li>
    </x-slot:account>
</x-site-footer>
