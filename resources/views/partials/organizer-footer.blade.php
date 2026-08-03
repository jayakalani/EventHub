<x-site-footer :home-href="route('organizer.dashboard')">
    <x-slot:explore>
        <li>
            <x-footer-link :href="route('organizer.events.index')" :active="request()->routeIs('organizer.events.*')">
                {{ t(['en' => 'Events', 'si' => 'ප්‍රසංග']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('organizer.hosts')" :active="request()->routeIs('organizer.hosts', 'organizer.hosts.*', 'organizer.host.*')">
                {{ t(['en' => 'Hosts', 'si' => 'සත්කාරක']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('organizer.calendar.index')" :active="request()->routeIs('organizer.calendar.*')">
                {{ t(['en' => 'Calendar', 'si' => 'දින දසුන']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('organizer.reports')" :active="request()->routeIs('organizer.reports', 'organizer.reports.*')">
                {{ t(['en' => 'Reports', 'si' => 'වාර්තා']) }}
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
            <x-footer-link :href="route('organizer.dashboard')" :active="request()->routeIs('organizer.dashboard')">
                {{ t(['en' => 'Dashboard', 'si' => 'විස්තර පුවරුව']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                {{ t(['en' => 'Profile', 'si' => 'පැතිකඩ']) }}
            </x-footer-link>
        </li>
    </x-slot:account>
</x-site-footer>
