<x-site-footer :home-href="route('cro.dashboard')">
    <x-slot:explore>
        <li>
            <x-footer-link :href="route('cro.inquiries.index')" :active="request()->routeIs('cro.inquiries.*')">
                {{ t(['en' => 'Inquiries', 'si' => 'විමසුම්']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('cro.complaints.index')" :active="request()->routeIs('cro.complaints.*')">
                {{ t(['en' => 'Complaints', 'si' => 'පැමිණිලි']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('cro.refund-requests.index')" :active="request()->routeIs('cro.refund-requests.*')">
                {{ t(['en' => 'Refunds', 'si' => 'ආපසු ගෙවීම්']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('cro.reports')" :active="request()->routeIs('cro.reports', 'cro.reports.*')">
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
            <x-footer-link :href="route('cro.dashboard')" :active="request()->routeIs('cro.dashboard')">
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
