<x-site-footer :home-href="route('dashboard')">
    <x-slot:explore>
        <li>
            <x-footer-link :href="route('admin.users')" :active="request()->routeIs('admin.users', 'admin.user.*', 'admin.employees.*', 'admin.employee.*')">
                {{ t(['en' => 'Users', 'si' => 'පරිශීලකයන්']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('admin.events.index')" :active="request()->routeIs('admin.events.*')">
                {{ t(['en' => 'Events', 'si' => 'ප්‍රසංග']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('admin.event-categories.index')" :active="request()->routeIs('admin.event-categories.*', 'admin.event.category.*')">
                {{ t(['en' => 'Categories', 'si' => 'ප්‍රවර්ග']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('dashboard').'#insights'" :active="request()->routeIs('dashboard')">
                {{ t(['en' => 'Insights', 'si' => 'විශ්ලේෂණ']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('dashboard').'#support'" :active="false">
                {{ t(['en' => 'Support', 'si' => 'සහාය']) }}
            </x-footer-link>
        </li>
        <li>
            <x-footer-link :href="route('admin.audit-logs')" :active="request()->routeIs('admin.audit-logs', 'admin.audit-logs.*')">
                {{ t(['en' => 'Audit Logs', 'si' => 'විගණන ලොග']) }}
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
            <x-footer-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
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
