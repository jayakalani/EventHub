<x-app-layout>
    <x-slot name="header">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 p-8 shadow-xl">

        <div class="absolute inset-0 opacity-10">
            <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white"></div>
            <div class="absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-white"></div>
        </div>

        <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">

            <div>
                <h1 class="text-3xl font-bold text-white">
                    Admin Dashboard
                </h1>

                <p class="mt-2 text-blue-100">
                    Welcome back. Monitor users, events, security, and platform activity.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.users') }}"
                    class="inline-flex items-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-indigo-600 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                    Manage Users
                </a>

                <a href="{{ route('admin.event-categories') }}"
                    class="inline-flex items-center rounded-2xl border border-white/30 bg-white/10 backdrop-blur-sm px-5 py-3 text-sm font-semibold text-white hover:bg-white/20 transition-all duration-300">
                    Event Categories
                </a>

                <a href="{{ route('admin.audit-logs') }}"
                    class="inline-flex items-center rounded-2xl border border-white/30 bg-white/10 backdrop-blur-sm px-5 py-3 text-sm font-semibold text-white hover:bg-white/20 transition-all duration-300">
                    Audit Logs
                </a>

                <a href="{{ route('admin.support-reports') }}"
                    class="inline-flex items-center rounded-2xl border border-white/30 bg-white/10 backdrop-blur-sm px-5 py-3 text-sm font-semibold text-white hover:bg-white/20 transition-all duration-300">
                    Support Reports
                </a>
            </div>

        </div>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">

            <div class="group bg-white rounded-3xl border border-slate-200 p-6 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Total Users
                        </p>

                        <h3 class="mt-2 text-4xl font-bold text-slate-900">
                            {{ $totalUsers ?? 0 }}
                        </h3>

                        <p class="mt-2 text-xs text-emerald-600 font-medium">
                            +12% this month
                        </p>
                    </div>

                    <div class="h-14 w-14 rounded-2xl bg-blue-100 flex items-center justify-center">
                        👥
                    </div>
                </div>

            </div>

            <div class="group bg-white rounded-3xl border border-slate-200 p-6 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">
                            Pending Verifications
                        </p>

                        <h3 class="mt-2 text-4xl font-bold text-amber-600">
                            {{ $pendingVerifications ?? 0 }}
                        </h3>

                        <p class="mt-2 text-xs text-slate-500">
                            Awaiting approval
                        </p>
                    </div>

                    <div class="h-14 w-14 rounded-2xl bg-amber-100 flex items-center justify-center">
                        ⏳
                    </div>
                </div>

            </div>

            <div class="group bg-white rounded-3xl border border-slate-200 p-6 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">
                            Locked Accounts
                        </p>

                        <h3 class="mt-2 text-4xl font-bold text-red-600">
                            {{ $lockedAccounts ?? 0 }}
                        </h3>

                        <p class="mt-2 text-xs text-slate-500">
                            Security restrictions
                        </p>
                    </div>

                    <div class="h-14 w-14 rounded-2xl bg-red-100 flex items-center justify-center">
                        🔒
                    </div>
                </div>

            </div>

            <div class="group bg-white rounded-3xl border border-slate-200 p-6 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1">

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">
                            Support — Pending
                        </p>

                        <h3 class="mt-2 text-4xl font-bold text-amber-600">
                            {{ $pendingSupportCount ?? 0 }}
                        </h3>

                        <p class="mt-2 text-xs text-slate-500">
                            {{ $totalInquiries ?? 0 }} inquiries · {{ $totalComplaints ?? 0 }} complaints
                        </p>
                    </div>

                    <div class="h-14 w-14 rounded-2xl bg-amber-100 flex items-center justify-center">
                        📋
                    </div>
                </div>

            </div>

        </div>

        <!-- Main Content -->
        <div class="grid gap-6 xl:grid-cols-3">

            <!-- Recent Activity -->
            <div class="xl:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm">

                <div class="border-b border-slate-100 px-8 py-6">
                    <h3 class="text-xl font-bold text-slate-900">
                        Recent Activity
                    </h3>

                    <p class="text-sm text-slate-500 mt-1">
                        Latest platform and user actions
                    </p>
                </div>

                <div class="p-8">

                    <div class="space-y-6">

                        <div class="flex gap-4">
                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                👤
                            </div>

                            <div class="flex-1">
                                <p class="font-semibold text-slate-900">
                                    New event organizer account created
                                </p>

                                <p class="text-sm text-slate-500">
                                    2 minutes ago
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                ✔
                            </div>

                            <div class="flex-1">
                                <p class="font-semibold text-slate-900">
                                    5 users verified their email addresses
                                </p>

                                <p class="text-sm text-slate-500">
                                    14 minutes ago
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                                🔐
                            </div>

                            <div class="flex-1">
                                <p class="font-semibold text-slate-900">
                                    2 suspicious accounts automatically locked
                                </p>

                                <p class="text-sm text-slate-500">
                                    37 minutes ago
                                </p>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- Quick Actions -->
            <aside class="bg-white rounded-3xl border border-slate-200 shadow-sm">

                <div class="border-b border-slate-100 px-8 py-6">
                    <h3 class="text-xl font-bold text-slate-900">
                        Quick Actions
                    </h3>

                    <p class="text-sm text-slate-500 mt-1">
                        Frequently used admin tools
                    </p>
                </div>

                <div class="p-6 space-y-4">

                    <a href="{{ route('admin.users') }}"
                        class="block rounded-2xl bg-slate-50 border border-slate-200 p-4 hover:bg-slate-100 transition">
                        <h4 class="font-semibold text-slate-900">
                            Manage Users
                        </h4>
                        <p class="text-sm text-slate-500 mt-1">
                            Create, edit and monitor accounts
                        </p>
                    </a>

                    <a href="{{ route('admin.audit-logs') }}"
                        class="block rounded-2xl bg-slate-50 border border-slate-200 p-4 hover:bg-slate-100 transition">
                        <h4 class="font-semibold text-slate-900">
                            Audit Logs
                        </h4>
                        <p class="text-sm text-slate-500 mt-1">
                            Review system activity history
                        </p>
                    </a>

                    <a href="{{ route('admin.support-reports') }}"
                        class="block rounded-2xl bg-slate-50 border border-slate-200 p-4 hover:bg-slate-100 transition">
                        <h4 class="font-semibold text-slate-900">
                            Support Reports
                        </h4>
                        <p class="text-sm text-slate-500 mt-1">
                            {{ $resolvedSupportCount ?? 0 }} resolved · export performance data
                        </p>
                    </a>

                    <a href="{{ route('admin.event-categories') }}"
                        class="block rounded-2xl bg-slate-50 border border-slate-200 p-4 hover:bg-slate-100 transition">
                        <h4 class="font-semibold text-slate-900">
                            Event Categories
                        </h4>
                        <p class="text-sm text-slate-500 mt-1">
                            Manage platform event categories
                        </p>
                    </a>

                </div>

            </aside>

        </div>

    </div>
</div>
```

</x-app-layout>
